<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_member_l;
use App\Model\User;
use Illuminate\Http\Request;
use Storage;
use Session;
use Lang;
use Html;
use Auth;

class SupplyMemberLicenseController extends Controller
{
    use SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyMemberLicenseController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員_擁有證照證明
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct()
    {
        //身分驗證
        $this->middleware('auth');
        //路由
        $this->hrefHome         = 'contractormember';
        $this->hrefIdentity     = 'contractormemberidentity/';
        $this->hrefMain         = 'contractormemberlicense';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'contractormemberlicense/';
        $this->hrefMainNew      = 'new_contractormemberlicense/';
        $this->routerPost       = 'postContractormemberlicense';
        $this->routerPost2      = 'contractormemberlicenseCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title4');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list4');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new4');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit4');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

        $this->fileSizeLimit1   = config('mycfg.file_upload_limit','102400');
        $this->fileSizeLimit2   = config('mycfg.file_upload_limit_name','10MB');
    }
    /**
     * 首頁內容
     *
     * @return void
     */
    public function index(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $no = 0;
        $out = $js ='';
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $mid       = SHCSLib::decode($request->mid); //成員帳號ID

        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param1 = 'mid='.$request->mid;
            $param2 = '?pid='.Session::get($this->langText.'.pid',SHCSLib::encode($supply_id));
            $supply = b_supply::getName($supply_id);
            $member = User::getName($mid);
            $isCloseType = User::isClose($mid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply.$Icon.$member;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->mid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome.$param2;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiSupplyMemberLicenseList($supply_id,$mid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if(!$isCloseType)
        {
            //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        }
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_32')];
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_71')];//證號
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_79')];//發證類型
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_74')];//發證日期
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_33')];//有效日期
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_34'),'style'=>'width:15%;']; //
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_35'),'style'=>'width:15%;']; //
        $heads[] = ['title'=>Lang::get($this->langText.'.supply_36'),'style'=>'width:15%;']; //

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $member; ///
                $name2        = $value->license; ///
                $name3        = $value->edate; ///
                $name4        = $value->license_code; ///
                $name5        = $value->sdate; ///
                $name6        = $value->edate_type_name; ///
                $show_name3   = $value->show_name3? $value->show_name3 : Lang::get('sys_btn.btn_29');
                $show_name4   = $value->show_name4? $value->show_name4 : Lang::get('sys_btn.btn_29');
                $show_name5   = $value->show_name5? $value->show_name5 : Lang::get('sys_btn.btn_29');
                $fileLink1    = ($value->filePath1)? $form->linkbtn($value->filePath1, $show_name3,4,'','','','_blank') : '';
                $fileLink2    = ($value->filePath2)? $form->linkbtn($value->filePath2, $show_name4,4,'','','','_blank') : '';
                $fileLink3    = ($value->filePath3)? $form->linkbtn($value->filePath3, $show_name5,4,'','','','_blank') : '';

                //按鈕
                $btn          = ($isCloseType)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param1),Lang::get('sys_btn.btn_13'),1); //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '4'=>[ 'name'=> $name4],
                            '6'=>[ 'name'=> $name6],
                            '5'=>[ 'name'=> $name5],
                            '3'=>[ 'name'=> $name3],
                            '11'=>[ 'name'=> $fileLink1],
                            '12'=>[ 'name'=> $fileLink2],
                            '13'=>[ 'name'=> $fileLink3],
                            '99'=>[ 'name'=> $btn ]
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $out .= $table->output();
        unset($table);


        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_table($tbTitle,$out));
        $contents = $content->output();

        //jsavascript
        $js = '$(document).ready(function() {
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                    
                } );';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $tBody     = [];
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $mid       = SHCSLib::decode($request->mid);
        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?mid='.$request->mid;
            $supply= b_supply::getName($supply_id);
            $member= User::getName($mid);
            $isMain= $request->has('isIdentity') ? 0 : 1;
        }

        $id = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack       = ($isMain ? $this->hrefMain : $this->hrefIdentity.$request->back).$param;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $isMain ? $this->getData($id) : $this->getApiSupplyMemberLicenseData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->e_license_id; //
            $A2         = $getData->edate; //
            $A3         = $getData->license; //
            $A4         = $getData->license_code; //
            $A5         = $getData->edate_type_name; //
            $A6         = $getData->sdate; //
            $A11        = $getData->show_name1; //
            $A12        = $getData->show_name2; //
            $A13        = $getData->show_name3; //
            $A14        = $getData->show_name4; //
            $A15        = $getData->show_name5; //
            $A16        = $getData->edate_type; //
            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)?   Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,  'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = $supply;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //成員姓名
        $html = $member;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'));
        //證明名稱
        $html = $A3;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
        //證明代碼
        $html = $A4;//$form->text('license_code',$A4);
        $form->add('nameT3', $html,$A11,1);
        //發證類型
        $html = $A5;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_79'),1);
        //證明－發證日期
        $html = $A6;//$form->date('sdate',$A6);
        $form->add('nameT3', $html,$A12,1);
        //證明－有效日期
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_33'),1);
        //證明1－檔案1
        if($A13)
        {
            $html  = ($getData->filePath1)? $form->linkbtn($getData->filePath1, Lang::get('btn.btn_29'),4,'','','','_blank') : '';
            //$html .= $form->file('file1');
            $form->add('nameT3', $html,$A13,1);
        }
        //證明2－檔案2
        if($A14)
        {
            $html  = ($getData->filePath2)? $form->linkbtn($getData->filePath2, Lang::get('btn.btn_29'),4,'','','','_blank') : '';
            //$html .= $form->file('file2');
            $form->add('nameT3', $html,$A14,1);
        }
        //證明3－檔案3
        if($A15)
        {
            $html  = ($getData->filePath3)? $form->linkbtn($getData->filePath3, Lang::get('btn.btn_29'),4,'','','','_blank') : '';
            //$html .= $form->file('file3');
            $form->add('nameT3', $html,$A15,1);
        }
        //停用
        $html = $form->checkbox('isClose','Y',$A99);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.supply_18'));
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('urlid',$request->mid);
        $submitDiv.= $form->hidden('isMain',$isMain);
        $submitDiv.= $form->hidden('license_id',$A1);
        $submitDiv.= $form->hidden('edate_type',$A16);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,2));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
            $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                endDate: "today",
                language: "zh-TW"
            });
            $("input[name=\'headImg\']").change(function() {
              readURL(this);
              $("#blah_div").hide();
            });
            function readURL(input) {
              if (input.files && input.files[0]) {
                var reader = new FileReader();
            
                reader.onload = function(e) {
                  $("#blah").attr("src", e.target.result);
                  $("#blah_div").show();
                }
            
                reader.readAsDataURL(input.files[0]);
              }
            }
        });';

        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id || !$request->license_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
//        elseif(!CheckLib::isDate($request->sdate))
//        {
//            return \Redirect::back()
//                ->withErrors(Lang::get($this->langText.'.supply_1004'))
//                ->withInput();
//        }elseif(strtotime($request->sdate) > strtotime(date('Y-m-d')))
//        {
//            return \Redirect::back()
//                ->withErrors(Lang::get($this->langText.'.supply_1027'))
//                ->withInput();
//        }
        else {
            $this->getBcustParam();
            $mid        = SHCSLib::decode($request->urlid);
            $supply_id  = Session::get($this->langText.'.supply_id',0);
            $id         = SHCSLib::decode($request->id);
            $ip         = $request->ip();
            $menu       = $this->pageTitleMain;
            $isMain     = $request->isMain;

            if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
            {
                $msg = Lang::get($this->langText.'.supply_1002');
                return \Redirect::back()->withErrors($msg);
            }
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $file1 = $file2 = $file3 ='';
        $file1N = $file2N = $file3N ='';

        if($isNew)
        {
            if(b_supply_member_l::isExist($supply_id,$mid,$request->license_id)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1005'))
                    ->withInput();
            }
            elseif($request->show_name3 && !$request->file1)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1030',['filename'=>$request->show_name3]))
                    ->withInput();
            }
            elseif($request->show_name4 && !$request->file2)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1030',['filename'=>$request->show_name4]))
                    ->withInput();
            }
            elseif($request->show_name5 && !$request->file3)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1030',['filename'=>$request->show_name5]))
                    ->withInput();
            }
        } else {
            if(b_supply_member_l::isExist($supply_id,$mid,$request->license_id,$id)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1005'))
                    ->withInput();
            }
        }

        //檔案
        if($request->hasFile('file1'))
        {
            $File       = $request->file1;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPG','PNG','GIF','PDF','JPEG'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10119'))
                    ->withInput();
            }elseif($filesize > $this->fileSizeLimit1){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file1N = $extension;
                $file1  = file_get_contents($File);
            }
        }
        //檔案
        if($request->hasFile('file2'))
        {
            $File       = $request->file2;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPG','PNG','GIF','PDF','JPEG'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10119'))
                    ->withInput();
            }elseif($filesize > $this->fileSizeLimit1){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file2N = $extension;
                $file2  = file_get_contents($File);
            }
        }
        //檔案
        if($request->hasFile('file3'))
        {
            $File       = $request->file3;
            $extension  = $File->extension();
            $filesize   = $File->getSize();
            //[錯誤]格式錯誤
            if(!in_array(strtoupper($extension),['JPG','PNG','GIF','PDF','JPEG'])){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10119'))
                    ->withInput();
            }elseif($filesize > $this->fileSizeLimit1){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                    ->withInput();
            } else {
                $file3N = $extension;
                $file3  = file_get_contents($File);
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
            $upAry['isClose']               = ($request->isClose == 'Y')? 'Y' : 'N';
        } else {
            $upAry['license_id']            = $request->license_id;
            $upAry['license_code']          = $request->license_code;
            $upAry['edate_type']            = $request->edate_type;
            $upAry['sdate']                 = $request->sdate;
            $upAry['file1']                 = $file1;
            $upAry['file1N']                = $file1N;
            $upAry['file2']                 = $file2;
            $upAry['file2N']                = $file2N;
            $upAry['file3']                 = $file3;
            $upAry['file3N']                = $file3N;
            $upAry['b_supply_id']           = $supply_id;
            $upAry['b_cust_id']             = $mid;
        }

        //dd($upAry);

        //新增
        if($isNew)
        {
            $ret = $this->createSupplyMemberLicense($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyMemberLicense($id,$upAry,$this->b_cust_id);
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to(($isMain ? $this->hrefMain : $this->hrefIdentity).'?mid='.$request->urlid);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 單筆資料 新增
     */
    public function create(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $supply_id = Session::get($this->langText.'.supply_id',0);
        $mid       = SHCSLib::decode($urlid);
        if(!$mid || !is_numeric($mid) || !b_supply_member::isExist($supply_id,$mid))
        {
            $msg = Lang::get($this->langText.'.supply_1002');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?mid='.$urlid;
            $supply= b_supply::getName($supply_id);
            $member= User::getName($mid);
            $isMain= $request->has('isIdentity') ? 0 : 1;
        }
        $edate_type     = $request->edate_type;
        $license_id     = $request->license_id;
        $selectAry      = e_license::getSelect();
        $edateTypeAry   = SHCSLib::getCode('LICENSE_ISSUING_KIND2');
        //view元件參數
        $hrefBack   = ($isMain ? $this->hrefMain : $this->hrefIdentity).$param;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        $hrefPost   = ($license_id && $edate_type)? $this->routerPost : $this->routerPost2 ;
        $btnPost    = ($license_id && $edate_type)? 'btn_7' : 'btn_37' ;
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($hrefPost,$urlid),'POST',1,TRUE);

        //承攬商
        $html = $supply;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //成員姓名
        $html = $member;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'));
        if(!$license_id || !$edate_type)
        {
            //發證類型
            $html = $form->select('edate_type',$edateTypeAry,1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_79'),1);
            //專業證照
            $html = $form->select('license_id',$selectAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
        }

        if($license_id && $edate_type)
        {
            list($show_name1,$show_name2,$show_name3,$show_name4,$show_name5,$edate_type2) = e_license::getShowList($license_id);
            list($issuing_kind,$limit_year1,$limit_year2) = e_license::getIssuingList($license_id);
            //發證類型
            $html = $form->hidden('edate_type',$edate_type);
            $html.= isset($edateTypeAry[$edate_type])? $edateTypeAry[$edate_type] : '';
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_79'),1);
            //專業證照
            $html = $form->hidden('license_id',$license_id);
            $html.= isset($selectAry[$license_id])? $selectAry[$license_id] : '';
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
            //證明代碼
            $html = $form->text('license_code');
            $form->add('nameT3', $html,$show_name1,1);
            //發證日期
            $html = $form->date('sdate');
            $form->add('nameT3', $html,$show_name2,1);
            //有效日期計算
            $memo = ($issuing_kind == 1)? '.supply_1028' : '.supply_1029' ;
            $html = Lang::get($this->langText.$memo,['year1'=>$limit_year1,'year2'=>$limit_year2]);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_80'),1);
            //證明1－檔案1
            if($show_name3)
            {
                $html = $form->file('file1');
                $html.= $form->hidden('show_name3',$show_name3);
                $form->add('nameT3', $html,$show_name3,1);
            }
            //證明1－檔案2
            if($show_name4)
            {
                $html = $form->file('file2');
                $html.= $form->hidden('show_name4',$show_name4);
                $form->add('nameT3', $html,$show_name4,1);
            }
            //證明1－檔案3
            if($show_name5)
            {
                $html = $form->file('file3');
                $html.= $form->hidden('show_name5',$show_name5);
                $form->add('nameT3', $html,$show_name5,1);
            }
        }


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$btnPost),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('b_supply_id',$supply_id);
        $submitDiv.= $form->hidden('mid',$mid);
        $submitDiv.= $form->hidden('urlid',$urlid);
        $submitDiv.= $form->hidden('isMain',$isMain);
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();
        //-------------------------------------------//
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '$(function () {
            $("#sdate").datepicker({
                format: "yyyy-mm-dd",
                endDate: "today",
                language: "zh-TW"
            });
        });';
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * 取得 指定對象的資料內容
     * @param int $uid
     * @return array
     */
    protected function getData($uid = 0)
    {
        $ret  = array();
        $data = Session::get($this->hrefMain.'.Record');
        //dd($data);
        if( $data && count($data))
        {
            if($uid)
            {
                foreach ($data as $v)
                {
                    if($v->id == $uid)
                    {
                        $ret = $v;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

}
