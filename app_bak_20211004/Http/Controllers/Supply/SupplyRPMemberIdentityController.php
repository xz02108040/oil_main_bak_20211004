<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\SessTraits;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyRPMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyRPMemberLicenseTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_license;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_ei;
use App\Model\View\view_door_supply_member;
use Html;
use App\Model\User;
use Illuminate\Http\Request;
use Storage;
use Session;
use Lang;
use Auth;

class SupplyRPMemberIdentityController extends Controller
{
    use SupplyRPMemberIdentityTrait,SupplyRPMemberLicenseTrait,SupplyMemberIdentityTrait,SupplyMemberLicenseTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPMemberIdentityController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員_工程身分申請單
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
        $this->hrefMain         = 'rp_contractormemberidentity';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractormemberidentity/';
        $this->hrefMainDetail2  = 'rp_contractormemberidentity2/';
        $this->hrefMainNew      = 'new_rp_contractormemberidentity/';
        $this->routerPost       = 'postContractorrpmemberidentity';

        $this->pageTitleMain    = Lang::get($this->langText.'.title8');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list8');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new8');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit8');//編輯

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
            $listAry = $this->getApiSupplyRPMemberIdentityMainList($aproc);

        } else {
            $listAry = $this->getApiSupplyRPMemberIdentityList($pid,0,0,$aproc);
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
        //搜尋
        $html = $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.supply_52'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $form->addRowCnt($html);
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
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_19')]; //成員
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_51')]; //工程身分
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
                    $name2        = $value->type; ///
                    $name4        = $value->apply_name; ///
                    $name5        = $value->apply_stamp; ///
                    $name6        = $value->aproc_name; ///
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //

                    $btnRoute     = ($aproc == 'A')? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($aproc == 'A')? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,4); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $name4],
                        '12'=>[ 'name'=> $name5],
                        '21'=>[ 'name'=> $name6,'label'=>$aprocColor],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
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
        $js = $contents = $A10 = $A11 = $A12 = $A13 = $A14 = $A20 = $A21 = $A22 = '';
        $lid = 0;
        $tBody     = [];
        $id         = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle;
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $pid        = $getData->b_supply_id;
            $project    = view_door_supply_member::getProjectID($getData->b_cust_id);

            $A1         = $getData->engineering_identity_id; //
            $A2         = $getData->b_cust_id; //
            $A3         = $getData->apply_name; //
            $A4         = $getData->apply_stamp; //
            $A5         = $getData->aproc_name; //
            $A6         = $getData->user; //
            $A7         = $getData->type; //
            $A8         = e_project_license::getUserIdentityAllName($getData->e_project_id,$getData->b_cust_id); //
            $A9         = e_project_l::getAllName($project,1); //
            //證照證明
            $getFileAry = $this->getApiSupplyRPMemberLicenseList($pid,0,0,$id);
            if(is_object($getFileAry))
            {
                foreach ($getFileAry as $val)
                {
                    $licenseAry[$val->id] = $val;
                }
                Session::put($this->hrefMain.'.identityApplyAry',$licenseAry);
            }

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = b_supply::getName($pid);
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //申請人
        $html = $A3;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A5;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //成員姓名
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程案件之工程身分
        $html = $A9;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_56'));
        //自己具備的工程身分
        $html = $A8;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_55'));
        //工程身分
        $html = HtmlLib::Color($A7,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_57'),1);
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //---證照證明 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_31'),2);
        $form->addHtml( $html );
        foreach ($licenseAry as $lid => $val)
        {
            //證件
            $lname = $val->license;
            $html  = HtmlLib::Color($lname,'blue',1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
            //證件
            $lname = $val->license_code;
            $html  = HtmlLib::Color($lname,'',1);
            $form->add('nameT3', $html,$val->show_name1,1);
            //有效期限
            $html = $val->edate;
            $form->add('nameT3', $html,$val->show_name2,1);
            //證明1－檔案1
            $html  = ($val->filePath1)? Html::image($val->filePath1,'',['class'=>'img-responsive','height'=>'30%']) : '';
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);
        }
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        $form->addHr();
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('b_cust_id',$A2);
        $submitDiv.= $form->hidden('type_id',$A1);
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
            $(".datetype").datepicker({
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
        if( !$request->id || !$request->type_id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif ($request->has('agreeN') && !$request->charge_memo)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1021'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $pid  = $request->pid;
            $menu = $this->pageTitleMain;
        }
        $isAgree = 0;
        $isNew   = ($id > 0)? 0 : 1;
        $action  = ($isNew)? 1 : 2;
        $licenseAry = [];
        //2-3.
        $identityApplyAry = Session::get($this->hrefMain.'.identityApplyAry',[]);
        if(!count($identityApplyAry))
        {
            //查無工程身份申請
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.supply_1022'))
                ->withInput();
        } else {
            $isApplyNum = 0;
            foreach ($identityApplyAry as $key => $val) {
                $licenseAry[$val->id]['edate'] = $val->edate;
                $licenseAry[$val->id]['license_code'] = $val->license_code;
                $licenseAry[$val->id]['file1'] = $val->file1;
                $licenseAry[$val->id]['file2'] = $val->file2;
                $licenseAry[$val->id]['file3'] = $val->file3;
            }
        }
        //dd($licenseAry);

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['license']           = $licenseAry;
        $upAry['type_id']           = $request->type_id;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        $upAry['b_supply_id']       = $pid;
        $upAry['b_cust_id']         = $request->b_cust_id;
        $upAry['charge_memo']       = $request->charge_memo;
        if($request->has('agreeY'))
        {
            $isAgree        = 1;
            $upAry['aproc'] = 'O';


            $upAry['b_supply_rp_member_ei_id']  = $id;
            $upAry['isIdentity']                = 1;
        }
        if($request->has('agreeN'))
        {
            $upAry['aproc'] = 'C';
        }
        //dd($upAry);
        //新增
        if($isNew)
        {
            //$ret = $this->createSupplyRPMember($upAry,$this->b_cust_id);
            $ret  = 0;
            $id  = $ret;
        } else {
            //修改
            $ret2 = $this->setSupplyRPMemberLicenseGroup($upAry['license'],$this->b_cust_id);
            $ret1 = $this->setSupplyRPMemberIdentity($id,$upAry,$this->b_cust_id);
            if($ret1 || $ret2 )
            {
                if( $ret1 <= 0 && $ret2 <= 0)
                {
                    $ret = -1;
                } else {
                    $ret = 1;
                }
            }
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
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_ei',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_member_l',$id);
                }
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_ei',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_supply_rp_member_l',$id);

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
        $js = $contents = $A10 = $A11 = $A12 = $A13 = $A14 = '';
        $lid = 0;
        $tBody     = [];
        $id         = SHCSLib::decode($urlid);
        $typeAry    = b_supply_engineering_identity::getSelect();
        $selectAry  = e_license::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle;
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $pid        = $getData->b_supply_id;

            $A1         = $getData->engineering_identity_id; //
            $A2         = $getData->b_cust_id; //
            $A3         = $getData->apply_name; //
            $A4         = $getData->apply_stamp; //
            $A5         = $getData->aproc_name; //
            $A6         = $getData->user; //
            $A7         = $getData->charge_memo; //
            $licenseAry = [];
            //證照證明
            $getFileAry = $this->getApiSupplyRPMemberLicenseList($pid,0,0,$id,0,'X');
            if(is_object($getFileAry))
            {
                foreach ($getFileAry as $val)
                {
                    $licenseAry[$val->id] = $val;
                }
            }

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //承攬商
        $html = b_supply::getName($pid);
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_12'));
        //申請人
        $html = $A3;
        $form->add('nameT6', $html,Lang::get($this->langText.'.supply_29'));
        //申請時間
        $html = $A4;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_28'));
        //進度
        $html = $A5;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));

        //審查事由
        $html = HtmlLib::Color($A7,'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));
        $form->addHr();
        //成員姓名
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程身分
        $html = isset($typeAry[$A1])? $typeAry[$A1] : '';
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_51'),1);
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //---證照證明 ---//
        $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_31'),2);
        $form->addHtml( $html );
        foreach ($licenseAry as $lid => $val)
        {
            //證件
            $lname = $val->license;
            $html  = HtmlLib::Color($lname,'blue',1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
            //證件
            $lname = $val->license_code;
            $html  = HtmlLib::Color($lname,'',1);
            $form->add('nameT3', $html,$val->show_name1,1);
            //有效期限
            //$html = $form->date('license_id['.$lid.'][edate]',$val->edate,4,'','datetype');
            $html = $val->edate;
            $html.= Lang::get($this->langText.'.supply_69');
            $form->add('nameT3', $html,$val->show_name2,1);
            //證明1－檔案1
            $html  = ($val->filePath1)? Html::image($val->filePath1,'',['class'=>'img-responsive','height'=>'30%']) : '';
            //$html .= $form->file('license_id['.$lid.'][file1]');
            $form->add('nameT3', $html,Lang::get($this->langText.'.supply_34'),1);
        }
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //Submit
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('lid',$lid);
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
