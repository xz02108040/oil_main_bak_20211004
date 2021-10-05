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
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_member_l;
use App\Model\User;
use Illuminate\Http\Request;
use Storage;
use Session;
use Lang;
use Auth;

class SupplyRPMemberLicenseController extends Controller
{
    use SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPMemberLicenseController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員_證照證明申請單
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
        $this->hrefMain         = 'rp_contractormemberlicense';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractormemberlicense/';
        $this->hrefMainDetail2  = 'rp_contractormemberlicense2/';
        $this->routerPost       = 'postContractorrpmemberlicense';

        $this->pageTitleMain    = Lang::get($this->langText.'.title9');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list9');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new9');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit9');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
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
        $out = $js = $supply = '';
        $supply_id = Session::get('user.supply_id',0);
        $closeAry  = SHCSLib::getCode('CLOSE');
        $aprocColorAry = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $aprocAry       = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC',1);
        //進度
        $aproc    = ($request->aproc)? $request->aproc : '';
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc','A');
        }
        //承攬商
        $pid      = $request->pid;
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$supply;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$pid)
        {
            $listAry = $this->getApiSupplyRPMemberLicenseMainList($aproc);

        } else {
            $listAry = $this->getApiSupplyRPMemberLicenseList($supply_id);
            Session::put($this->hrefMain.'.Record',$listAry);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        }
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        if(!$pid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_12')]; //承攬商
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_29')]; //申請人
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_28')]; //申請時間
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')];
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_32')];
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_33')];
        }

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                if(!$pid)
                {
                    $id           = $value->b_supply_id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.$id),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->user; ///
                    $name2        = $value->license; ///
                    $name3        = $value->edate; ///
                    $name4        = $value->apply_name; ///
                    $name5        = $value->apply_stamp; ///
                    $name6        = $value->aproc_name; ///
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //

                    //按鈕
                    $btnRoute     = ($aproc == 'A')? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($aproc == 'A')? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $name4],
                        '12'=>[ 'name'=> $name5],
                        '21'=>[ 'name'=> $name6,'label'=>$aprocColor],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '99'=>[ 'name'=> $btn],
                    ];
                }
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
        $id = SHCSLib::decode($urlid);
        $selectAry = e_license::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $pid        = $getData->b_supply_id; //

            $A1         = $getData->e_license_id; //
            $A2         = $getData->edate; //
            $A3         = $getData->user; //
            $A4         = $getData->b_cust_id; //
            $A5         = $getData->type; //

            $A10        = $getData->apply_user; //
            $A11        = $getData->apply_stamp; //
            $A12        = $getData->aproc_name; //

            $A20         = $getData->file1; //
            $A21         = $getData->file2; //
            $A22         = $getData->file3; ///


            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = b_supply::getName($pid);;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //申請人
        $html = $A10;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A11;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A12;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //成員姓名
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'));
        //工程身分
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_51'));
        //證件
        $html = $form->select('license_id',$selectAry,$A1);
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_32'),1);
        //有效期限
        $html = $form->date('edate',$A2);
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_33'),1);
        //證明1－檔案1
        $html  = ($getData->filePath1)? $form->linkbtn($getData->filePath1, Lang::get('sys_btn.btn_29'),4) : '';
        $html .= $form->file('file1');
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);
        //證明1－檔案2
        $html  = ($getData->filePath2)? $form->linkbtn($getData->filePath2, Lang::get('sys_btn.btn_29'),4) : '';
        $html .= $form->file('file2');
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_35'));
        //證明1－檔案3
        $html  = ($getData->filePath3)? $form->linkbtn($getData->filePath3, Lang::get('sys_btn.btn_29'),4) : '';
        $html .= $form->file('file3');
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_36'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('b_cust_id',$A4);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('filepath1',$A20);
        $submitDiv.= $form->hidden('filepath2',$A21);
        $submitDiv.= $form->hidden('filepath3',$A22);
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
                startDate: "today",
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //資料不齊全
        if( !$request->id || !$request->license_id || !$request->edate)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(!CheckLib::isDate($request->edate))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1004'))
                ->withInput();
        }elseif(strtotime($request->edate) < strtotime(date('Y-m-d')))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1006'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $pid        = $request->pid;
            $id         = SHCSLib::decode($request->id);
            $ip         = $request->ip();
            $menu       = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $file1 = $file2 = $file3 ='';
        $file1N = $file2N = $file3N ='';

        if($isNew)
        {
//            if(b_supply_member_l::isExist($mid,$request->license_id)){
//                return \Redirect::back()
//                    ->withErrors(Lang::get($this->langText.'.supply_1005'))
//                    ->withInput();
//            }
            if($request->license_id && (!$request->edate || (!$request->file1 && !$request->file2 && !$request->file3)))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1003'))
                    ->withInput();
            }
            elseif(($request->edate || (!$request->file1 && !$request->file2 && !$request->file3)) && !$request->license_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.supply_1003'))
                    ->withInput();
            }
        } else {
//            if(b_supply_member_l::isExist($mid,$request->license_id,$id)){
//                return \Redirect::back()
//                    ->withErrors(Lang::get($this->langText.'.supply_1005'))
//                    ->withInput();
//            }
        }

        //檔案
        if($request->hasFile('file1'))
        {
            $File       = $request->file1;
            $extension  = $File->extension();
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
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
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
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
            //[錯誤]格式錯誤
            if(in_array(strtoupper($extension),['EXE','COM','RUN','APP','SH'])){
                return \Redirect::back()
                    ->withErrors($extension.Lang::get('sys_base.base_10120'))
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
        } else {

        }
        $upAry['license_id']           = $request->license_id;
        $upAry['edate']                = $request->edate;
        $upAry['file1']                = $file1;
        $upAry['file1N']               = $file1N;
        $upAry['file2']                = $file2;
        $upAry['file2N']               = $file2N;
        $upAry['file3']                = $file3;
        $upAry['file3N']               = $file3N;
        $upAry['b_supply_id']          = $pid;
        $upAry['b_cust_id']            = isset($request->b_cust_id)? $request->b_cust_id : 0;
        $upAry['b_supply_member_ei_id']= isset($request->ei_id)? $request->ei_id : 0;
        $upAry['isClose']              = ($request->isClose == 'Y')? 'Y' : 'N';
        $upAry['charge_memo']          = $request->charge_memo;

        if($request->has('agreeY'))
        {
            $isAgree        = 1;
            $upAry['aproc'] = 'O';

            $upAry['b_supply_rp_member_l_id']   = $id;
            $upAry['filepath1']     = $request->filepath1;
            $upAry['filepath2']     = $request->filepath2;
            $upAry['filepath3']     = $request->filepath3;
            $upAry['isLicense']    = 1;
        }
        if($request->has('agreeN'))
        {
            $upAry['aproc'] = 'C';
        }
        //dd($upAry);

        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPMemberLicense($upAry,$this->b_cust_id);
            $ret  = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPMemberLicense($id,$upAry,$this->b_cust_id);
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
                if($isAgree)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * 單筆資料 編輯
     */
    public function show2(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $tBody     = [];
        $id = SHCSLib::decode($urlid);
        $selectAry = e_license::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $pid        = $getData->b_supply_id; //

            $A1         = $getData->e_license_id; //
            $A2         = $getData->edate; //
            $A3         = $getData->user; //
            $A4         = $getData->b_cust_id; //
            $A5         = $getData->type; //

            $A10        = $getData->apply_user; //
            $A11        = $getData->apply_stamp; //
            $A12        = $getData->aproc_name; //


            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = b_supply::getName($pid);;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //申請人
        $html = $A10;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A11;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A12;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //成員姓名
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'));
        //工程身分
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_51'));
        //證件
        $html = isset($selectAry[$A1])? $selectAry[$A1] : '';
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_32'),1);
        //有效期限
        $html = $A2;
        $form->add('nameT3', $html,Lang::get('sys_supply.supply_33'),1);
        //證明1－檔案1
        $html  = ($getData->filePath1)? $form->linkbtn($getData->filePath1, Lang::get('sys_btn.btn_29'),4) : '';
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);
        //證明1－檔案2
        $html  = ($getData->filePath2)? $form->linkbtn($getData->filePath2, Lang::get('sys_btn.btn_29'),4) : '';
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_35'));
        //證明1－檔案3
        $html  = ($getData->filePath3)? $form->linkbtn($getData->filePath3, Lang::get('sys_btn.btn_29'),4) : '';
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_36'));
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
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
                startDate: "today",
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
