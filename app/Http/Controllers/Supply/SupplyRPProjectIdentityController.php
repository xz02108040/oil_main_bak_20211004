<?php

namespace App\Http\Controllers\Supply;

use Auth;
use Html;
use Lang;
use Session;
use Storage;
use App\Lib\LogLib;
use App\Model\User;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Lib\TableLib;
use App\Lib\ContentLib;
use App\Model\sys_code;
use App\Model\sys_param;
use Illuminate\Http\Request;
use App\Model\Supply\b_supply;
use App\Http\Traits\SessTraits;
use App\Http\Controllers\Controller;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_l;
use App\Model\Supply\b_supply_member_l;
use App\Model\Supply\b_supply_member_ei;
use App\Model\View\view_door_supply_member;
use App\Model\Engineering\e_project_license;
use App\Model\Supply\b_supply_engineering_identity;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyRPMemberLicenseTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Supply\SupplyRPMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;
use App\Http\Traits\Engineering\EngineeringMemberIdentityTrait;

class SupplyRPProjectIdentityController extends Controller
{
    use SupplyRPProjectLicenseTrait,EngineeringMemberIdentityTrait,SupplyMemberLicenseTrait,SessTraits;
    use EngineeringMemberIdentityTrait,EngineeringMemberTrait;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPMemberIdentityController
    |--------------------------------------------------------------------------
    |
    | 承攬商成員_工程案件之工程身分申請
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
        $this->hrefMain         = 'rp_contractorprojectidentity';
        $this->langText         = 'sys_supply';

        $this->hrefMainDetail   = 'rp_contractorprojectidentity/';
        $this->hrefMainDetail2  = 'rp_contractorprojectidentity2/';
        $this->hrefMainNew      = 'new_rp_contractorprojectidentity/';
        $this->routerPost       = 'postContractorrpprojectidentity';

        $this->pageTitleMain    = Lang::get($this->langText.'.title16');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list16');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new16');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit16');//編輯

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
        $aprocColorAry  = ['A'=>1,'P'=>4,'R'=>4,'O'=>2,'C'=>5];
        $aprocAry       = SHCSLib::getCode('RP_PROJECT_LICENSE_APROC',1);
        //允許管理的工程案件
        $allowProjectAry= $this->allowProjectAry;
        //進度
        $aproc    = ($request->aproc)? $request->aproc : '';
        if($aproc)
        {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        } else {
            $aproc = Session::get($this->hrefMain.'.search.aproc',($this->isRootDept? 'P' : 'A'));
        }
        //工安課審查階段
        $allowProjectAry = ($this->isRootDept)? [] : $allowProjectAry;
        //承攬商
        $pid      = SHCSLib::decode($request->pid);
        if($pid)
        {
            $supply= b_supply::getName($pid);
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        Session::put($this->hrefMain.'.isRootDept',$this->isRootDept);
        Session::put($this->hrefMain.'.allowProjectAry',$allowProjectAry);


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
            $listAry = $this->getApiSupplyRPProjectMemberLicenseMainList($aproc,$allowProjectAry);

        } else {
            $listAry = $this->getApiSupplyRPProjectMemberLicenseList($pid,$aproc,$allowProjectAry);
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
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_2')]; //類型
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_52')]; //進度
            $heads[] = ['title'=>Lang::get($this->langText.'.supply_49')]; //工程案件
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
                    $name1        = b_supply::getName($value->b_supply_id); //
                    $name2        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕
                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->user; ///
                    $name2        = $value->engineering_identity_name; ///
                    $name4        = $value->apply_name; ///
                    $name5        = $value->apply_stamp; ///
                    $name6        = $value->aproc_name; ///
                    $name7        = $value->project; ///
                    $name8        = $value->order_type_name;
                    $aprocColor   = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 1; //
                    $isCharge     = ($this->isRootDept && $aproc == 'P')? 1 : ((!$this->isRootDept && $aproc == 'A')? 1 : 0);

                    $btnRoute     = ($isCharge)? $this->hrefMainDetail : $this->hrefMainDetail2;
                    $btnName      = ($isCharge)? Lang::get('sys_btn.btn_21') : Lang::get('sys_btn.btn_60');
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($btnRoute,$id),$btnName,4); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $name4],
                        '12'=>[ 'name'=> $name5],
                        '13'=>[ 'name'=> $name8],
                        '21'=>[ 'name'=> $name6,'label'=>$aprocColor],
                        '7'=>[ 'name'=> $name7],
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
            $eid        = $getData->e_project_id;
            //$project    = view_door_supply_member::getProjectID($getData->b_cust_id);
            $A1         = $getData->engineering_identity_id; //
            $A2         = $getData->b_cust_id; //
            $A3         = $getData->apply_name; //
            $A4         = $getData->apply_stamp; //
            $A5         = $getData->aproc_name; //
            $A6         = $getData->user; //
            $A7         = $getData->engineering_identity_name; //
            $A8         = e_project_license::getUserIdentityAllName($getData->e_project_id,$getData->b_cust_id); //
//            $A9         = e_project_l::getAllName($project,1); //
            $A11        = $getData->charge_kind; //
            $A12        = $getData->charge_kind_name; //
            $A13        = $getData->aproc; //
            $A15        = $getData->project; //
            $A14        = $A11 == 2? '('.HtmlLib::Color($A12,'red',1).')' : ''; //
            $A16        = $getData->order_type_name; //
            $A17        = $getData->order_type; //
            $A21        = $getData->charge_name1; //
            $A22        = $getData->charge_stamp1; //
            $A23        = $getData->charge_memo1; //

            $A31        = $getData->charge_name2; //
            $A32        = $getData->charge_stamp2; //
            $A33        = $getData->charge_memo2; //
            //證照證明
            $licenseAry = $this->getApiSupplyMemberLicenseData($getData->b_supply_member_l_id);
            $licenseAry2 = $this->getApiSupplyMemberLicenseData($getData->b_supply_member_l_id2);

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
        //類型
        $html = $A16;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_2'));
        //進度
        $html = $A5.$A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //成員姓名
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程案件
        $html = $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_49'));
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
        $form->addHtml($html);
        $LicenseView = $this->LicenseView($licenseAry, $form);
        $LicenseView2 = $this->LicenseView($licenseAry2, $form);
        if (!$LicenseView && !$LicenseView2) {
            $html  = HtmlLib::Color(Lang::get($this->langText . '.supply_1045'), 'red', 1);
            $form->add('nameT4', $html, Lang::get($this->langText . '.supply_32'), 1);
        }
        
        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //---　審查紀錄 ---//
        if($A13 != 'A')
        {
            $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_82'),4);
            $form->addHtml( $html );
            //監造審查
            if($A21)
            {
                $html  = $A21;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_83'),1);
                $html  = $A22;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A23,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //工安審查
            if($A31)
            {
                $html  = $A31;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_86'),1);
                $html  = $A32;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A33,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
        }

        $form->addHr();
        //審查事由
        $html = $form->textarea('charge_memo');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.supply_1021'),'red',1);
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_50'));

        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_38'),'4','editY').'&nbsp;';
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('eid',$eid);
        $submitDiv.= $form->hidden('b_cust_id',$A2);
        $submitDiv.= $form->hidden('type_id',$A1);
        $submitDiv.= $form->hidden('charge_kind',$A11);
        $submitDiv.= $form->hidden('aproc',$A13);
        $submitDiv.= $form->hidden('order_type',$A17);
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
        // 檢查該成員的證照是否存在
        $isApplyYN = e_project_license::getUserlicense($request->eid, $request->b_cust_id, $request->type_id);
        // 檢查身分是否存在
        switch ($request->order_type) {
            case '2':
                //轉移，檢查舊身分是否存在
                $chg_identity = ($request->type_id == '1') ? '2' : '1';
                $isExist = e_project_license::isExist($request->eid, $request->b_cust_id, $chg_identity);
                break;
                //作廢，檢查新身分是否可審核
            default:
                $isExist = e_project_license::isExist($request->eid, $request->b_cust_id, $request->type_id);
                break;
        }
        // 是否存在【工安】【工負】工程身分
        $isAd = e_project_license::isAd($request->eid,$request->b_cust_id,$request->type_id);

        //資料不齊全
        if( !$request->id )
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
        //(申請/補身分/轉移)同意時，檢查成員是否具備有效工程身分
        elseif ($request->has('agreeY') && $isApplyYN == 'Y' && $request->order_type < 3) 
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1005'))
                ->withInput();
        }
        //2020-11-26 中油規則: 工安 & 工負 不可同時擔任
        elseif($request->has('agreeY') && $isAd && $request->order_type == '1')
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_supply.supply_1071'))
                ->withInput();
        }
        //轉移/作廢同意時，檢查成員該工程身分是否存在
        elseif ($request->has('agreeY') && !$isExist && $request->order_type >= '2') 
        {
            return \Redirect::back()
            ->withErrors(Lang::get('sys_supply.supply_1070'))
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
        //dd($licenseAry);

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['b_cust_id']         = $request->b_cust_id;
        $upAry['charge_memo']       = $request->charge_memo;
        if($request->has('agreeY'))
        {
            $isAgree        = 1;
            $upAry['aproc'] = ($request->charge_kind == 2 && $request->aproc == 'A')? 'P' : 'O';
        }
        if($request->has('agreeN'))
        {
            $upAry['aproc'] = 'C';
        }
        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret  = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setSupplyRPProjectMemberLicense($id,$upAry,$this->b_cust_id);
        }
        //2-1. 更新成功
        if($ret)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else if ($ret === -2) {
                //該成員已配卡，必須先進行退卡才能繼續
                $msg = Lang::get('sys_base.base_10174');
                return \Redirect::back()->withErrors($msg);
            } else if ($ret === -3) {
                //申請單狀態為審核結果階段，不可異動！
                $msg = Lang::get('sys_base.base_10187');
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
            $A7         = $getData->engineering_identity_name; //
            $A8         = e_project_license::getUserIdentityAllName($getData->e_project_id,$getData->b_cust_id); //
//            $A9         = e_project_l::getAllName($project,1); //
            $A11        = $getData->charge_kind; //
            $A12        = $getData->charge_kind_name; //
            $A13        = $getData->aproc; //
            $A14        = $A11 == 2? '('.HtmlLib::Color($A12,'red',1).')' : ''; //
            $A16        = $getData->order_type_name; //

            $A21        = $getData->charge_name1; //
            $A22        = $getData->charge_stamp1; //
            $A23        = $getData->charge_memo1; //

            $A31        = $getData->charge_name2; //
            $A32        = $getData->charge_stamp2; //
            $A33        = $getData->charge_memo2; //
            //證照證明
            $licenseAry = $this->getApiSupplyMemberLicenseData($getData->b_supply_member_l_id, '');
            $licenseAry2 = $this->getApiSupplyMemberLicenseData($getData->b_supply_member_l_id2, '');

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
        //類型
        $html = $A16;
        $form->add('nameT2', $html,Lang::get($this->langText.'.supply_2'));
        //進度
        $html = $A5.$A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_52'));
        //成員姓名
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.supply_19'),1);
        //工程案件之工程身分
//        $html = $A9;
//        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_56'));
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
        $LicenseView = $this->LicenseView($licenseAry, $form);
        $LicenseView2 = $this->LicenseView($licenseAry2, $form);
        if (!$LicenseView && !$LicenseView2) {
            $html  = HtmlLib::Color(Lang::get($this->langText . '.supply_1045'), 'red', 1);
            $form->add('nameT4', $html, Lang::get($this->langText . '.supply_32'), 1);
        }

        //Box End
        $html = HtmlLib::genBoxEnd();
        $form->addHtml($html);

        //---　審查紀錄 ---//
        if($A13 != 'A')
        {
            $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.supply_82'),4);
            $form->addHtml( $html );
            //監造審查
            if($A21)
            {
                $html  = $A21;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_83'),1);
                $html  = $A22;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A23,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //工安審查
            if($A31)
            {
                $html  = $A31;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_86'),1);
                $html  = $A32;
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_84'),1);
                $html  = HtmlLib::Color($A33,'',1);
                $form->add('nameT3', $html,Lang::get($this->langText.'.supply_85'),1);
            }
            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
        }

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

    /**
     * 證照證明畫面
     * @param $licenseAry
     * @return boolean
     */
    public function LicenseView($licenseAry,$form)
    {   
        if(empty($licenseAry)) return 0;
        $today     = date('Y-m-d');
         
        //證件
        $lname = $licenseAry->license;
        $html  = HtmlLib::Color($lname,'blue',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.supply_32'),1);
        //證號
        $lname = $licenseAry->license_code;
        $html  = HtmlLib::Color($lname,'',1);
        $form->add('nameT3', $html,$licenseAry->show_name1,1);
        //有效期限
        $html = $licenseAry->sdate.' ~ '.$licenseAry->edate.'('.HtmlLib::Color($licenseAry->edate_type_name,'',1).')';
        $form->add('nameT3', $html,$licenseAry->show_name2,1);
        //證照過期提示文字
        if ($licenseAry->isClose == 'Y' || $licenseAry->edate < $today) {
            $html = HtmlLib::Color(Lang::get($this->langText . '.supply_1074'), 'red', 1);
            $form->addRow($html);
        }
        //證明1－檔案1
        if($licenseAry->show_name3)
        {
            $html  = ($licenseAry->filePath1)? HtmlLib::btn($licenseAry->filePath1,Lang::get('sys_btn.btn_29'),2,'','','','_blank') : '';
            $form->add('nameT3', $html,$licenseAry->show_name3,1);
        }
        //證明1－檔案2
        if($licenseAry->show_name4)
        {
            $html  = ($licenseAry->filePath2)? HtmlLib::btn($licenseAry->filePath2,Lang::get('sys_btn.btn_29'),2,'','','','_blank') : '';
            $form->add('nameT3', $html,$licenseAry->show_name4,1);
        }
        //證明1－檔案3
        if($licenseAry->show_name5)
        {
            $html  = ($licenseAry->filePath3)? HtmlLib::btn($licenseAry->filePath3,Lang::get('sys_btn.btn_29'),2,'','','','_blank') : '';
            $form->add('nameT3', $html,$licenseAry->show_name5,1);
        }

        return isset($licenseAry->license) ? True : False;
    }
}
