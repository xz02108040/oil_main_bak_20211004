<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\ViolationContractorTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Excel;

class ViolationContractorController extends Controller
{
    use ViolationContractorTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | ViolationContractorController
    |--------------------------------------------------------------------------
    |
    | 人員違規 維護
    |
    */

    /**
     * 環境參數
     */
    protected $redirectTo = '/';

    /**
     * 建構子
     */
    public function __construct(Request $request)
    {
        //身分驗證
        $this->middleware('auth');
        //讀取選限
        $this->uri              = SHCSLib::getUri($request->route()->uri);
        $this->isWirte          = 'N';
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'eviolationcontractor';
        $this->hrefExcel        = 'eviolationcontractorexcel';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'eviolationcontractor/';
        $this->hrefMainNew      = 'new_eviolationcontractor';
        $this->routerPost1      = 'postEViolationcontractor';
        $this->routerPost2      = 'eviolationcontractorCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title15');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list15');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new15');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit15');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_29');//[按鈕]下載

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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js ='';
        $no  = 0;
        $parent    = ($request->pid)? SHCSLib::decode($request->pid) : 0;
        Session::put($this->hrefMain.'.search.pid',$parent);
        $supplyAry = b_supply::getSelect();
        $bid       = ($parent)? $parent : $request->bid;
        $sdate     = $request->sdate;
        $edate     = $request->edate;
        if($request->has('clear'))
        {
            $bid = $sdate = $edate = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate','');
        } else {
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate','');
        } else {
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
//        dd($search,$supplyAry);
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        $hrefExcel= $this->hrefExcel;
        $btnExcel = $this->pageExcelBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$parent)
        {
            $search = [$bid,$sdate,$edate];
            $listAry = $this->getApiViolationContractorSupplyList($search);
        } else {
            $search = [$bid,'',$sdate,$edate,0,0,0,''];
            $listAry = $this->getApiViolationContractorList($search);
        }
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($parent)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
            $form->addLinkBtn($hrefExcel, $btnExcel,4); //返回
        }
        $form->addHr();
        //搜尋
        $html = $form->select('bid',$supplyAry,$bid,2,Lang::get($this->langText.'.engineering_7'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_8'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1038'),'red',1);
        $form->addRow($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        if(!$parent)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_7')];  //負責廠商
            $heads[] = ['title'=>Lang::get('sys_supply.supply_3')];             //負責人
            $heads[] = ['title'=>Lang::get('sys_supply.supply_4')];             //統編
            $heads[] = ['title'=>Lang::get('sys_supply.supply_9')];             //電話
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_14')]; //成員姓名
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_139')]; //工號
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_51')]; //違規
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_60')]; //限制進出
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_52')]; //違規分類
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_53')]; //違規法規
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_55')]; //違規罰則
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_58')]; //再次在廠日期
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                if(!$parent)
                {
                    $name1        = $value->name; //
                    $name2        = $value->boss_name; //
                    $name3        = $value->tax_num; //
                    $name4        = $value->tel1 ?  $value->tel1 : ''; //

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $name1          = $value->user; //
                    $name1         .= '<br/>'.(($this->isSuperUser)? $value->bc_id : SHCSLib::genBCID($value->bc_id)); //
                    $name2          = $value->project; //
                    $name11         = $value->permit_no; //
                    $name3          = $value->violation_record1; //
                    $name4          = $value->apply_stamp; //
                    $name5          = $value->violation_record4; //
                    $name6          = $value->violation_record2; //
                    $name7          = $value->violation_record3; //
                    $name8          = $value->limit_edate; //
                    $Color          = 0; //

                    $isEdit         = strtotime($value->limit_edate) > strtotime(date('Y-m-d'))? 1 : 0;
                    $nameBtn        = ($isEdit)? 'btn_13' : 'btn_30';
                    $nameColor      = ($isEdit)? 4 : 1;
                    //按鈕
                    $btn            = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.'.$nameBtn),$nameColor) : ''; //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '11'=>[ 'name'=> $name11],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                        '7'=>[ 'name'=> $name7],
                        '8'=>[ 'name'=> $name8,'label'=>$Color],
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
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
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $selectAry  = e_violation::getSelect();
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
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            $today = date('Y-m-d');
            //資料明細
            $A1         = $getData->project; //
            $A2         = $getData->supply; //
            $A3         = $getData->user; //
            $A4         = SHCSLib::genBCID($getData->bc_id); //
            $A5         = $getData->e_violation_id; //
            $A6         = $getData->violation_record1; //
            $A7         = $getData->violation_record2; //
            $A8         = $getData->violation_record3; //
            $A9         = $getData->violation_record4; //
            $A10        = $getData->isControl;
            $A11        = $getData->apply_stamp; //
            $A12        = ($getData->isControl == '是')? $getData->limit_sdate.' ～ '.$getData->limit_edate : ''; //
            $A13        = $getData->e_project_id; //
            $A14        = $getData->b_supply_id; //
            $A15        = $getData->b_cust_id; //
            $A16        = $getData->e_violation_complain_id; //
            $A17        = $getData->charge_user; //
            $A18        = $getData->charge_stamp; //
            $A19        = $getData->charge_memo; //
            $A21        = 0;
            $A22        = $getData->limit_edate1;
            $A23        = $getData->limit_edate2;
            if($getData->limit_edate)
            {
                $A21        = (strtotime($getData->limit_edate) > strtotime($today))? 1 : 0; //

            }
            $pid        = SHCSLib::encode($A14); //

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost1,$id),'POST',1,TRUE);
        //工程案件
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'));
        //負責承攬商
        $html = $A2.'-'.$A3.' ('.$A4.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_59'));
        //違規時間
        $html = $A11;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_60'));
        //違規事項
        $html = $A6.' ('.$A9.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_51'));
        //法規
        $html = $A7;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_53'));
        //罰條
        $html = $A8;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_55'));
        //是否管制進出
        $html = $A10;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_56'));
        //再次入場時間
        $html = $A12;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_61'));

        //申訴
        if($A16)
        {
            $form->addHr();
            $html = '';
            //table
            $table = new TableLib();
            //標題
            $heads[] = ['title' => 'NO'];
            $heads[] = ['title' => Lang::get($this->langText.'.engineering_167')]; //申訴前日期
            $heads[] = ['title' => Lang::get($this->langText.'.engineering_168')]; //申訴後日期
            $heads[] = ['title' => Lang::get($this->langText.'.engineering_69')]; //審查人
            $heads[] = ['title' => Lang::get($this->langText.'.engineering_71')]; //審查時間
            $heads[] = ['title' => Lang::get($this->langText.'.engineering_70')]; //審查事由
            $table->addHead($heads, 0);
            $no = 1;

            $tBody[] = [
                '0' => ['name' => $no++, 'b' => 1, 'style' => 'width:5%;'],
                '1' => ['name' => $A22],
                '2' => ['name' => $A23],
                '3' => ['name' => $A17],
                '4' => ['name' => $A18],
                '5' => ['name' => $A19],
            ];
            
            $listAry = $this->getApiViolationContractorHistoryList($id);
            if (count($listAry)) {
                foreach ($listAry as $value) {
                    $tBody[] = [
                        '0' => ['name' => $no++, 'b' => 1, 'style' => 'width:5%;'],
                        '1' => ['name' => $value['limit_edate1'] . ' => ' . $value['limit_edate2']],
                        '2' => ['name' => $value['charge_user_name']],
                        '3' => ['name' => $value['charge_stamp']],
                        '4' => ['name' => $value['charge_memo']],
                    ];
                }
            }

            $table->addBody($tBody);
            // 輸出
            $html .= $table->output();
            $form->addRow($html, 8, 1);
        }

        if($A21)
        {
            $form->addHr();
            //違規事項
            $html = $form->select('e_violation_id',$selectAry,$A5);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_51'),1);
            //違規時間點
            $html  = $form->date('apply_date','',2);
            $html .= $form->time('apply_time','',2);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_60'),1);
            //停用
            $html = $form->checkbox('isClose','Y',$A99);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
            if($A99)
            {
                $html = $A97;
                $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
            }
        }

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv = '';
        if($A21)
        {
            $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack.'?pid='.$pid, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('e_project_id',$A13);
        $submitDiv.= $form->hidden('b_supply_id',$A14);
        $submitDiv.= $form->hidden('b_cust_id',$A15);
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
            $("#apply_date").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            $("#apply_time").timepicker({
                showMeridian: false,
                defaultTime: false,
                timeFormat: "HH:mm"
            })
        });
        $(document).ready(function() {
            $("#table1").DataTable({
                "language": {
                "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
            }
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
        if( !$request->has('agreeY') || !$request->id || !$request->b_supply_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif(!$request->e_project_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1010'))
                ->withInput();
        }
        elseif(!$request->b_cust_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1017'))
                ->withInput();
        }
        elseif($request->isClose == 'Y')
        {

        } else {
            if(!$request->e_violation_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1014'))
                    ->withInput();
            }
            elseif(!$request->apply_date || !CheckLib::isDate($request->apply_date) || !$request->apply_time || !CheckLib::isTime($request->apply_time))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1015'))
                    ->withInput();
            }
            elseif( strtotime($request->apply_date) > strtotime(date('Y-m-d')) )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1016'))
                    ->withInput();
            }
        }
        $this->getBcustParam();
        $id    = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $pid  = SHCSLib::encode($request->b_supply_id);
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }

        $upAry['e_violation_id']    = $request->e_violation_id;

        if($request->isClose != 'Y')
        {
            $upAry['e_project_id']      = isset($request->e_project_id)? $request->e_project_id : 0;
            $upAry['wp_work_id']        = isset($request->wp_work_id)? $request->wp_work_id : 0;
            $upAry['b_supply_id']       = $request->b_supply_id;
            $upAry['b_cust_id']         = $request->b_cust_id;
            $upAry['apply_date']        = $request->apply_date;
            $upAry['apply_time']        = $request->apply_time;
            $upAry['apply_stamp']       = date('Y-m-d H:i:m',strtotime($request->apply_date.''.$request->apply_time));
        } else {
            $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';
        }
        //新增
        if($isNew)
        {
            $ret = $this->createViolationContractor($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setViolationContractor($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'e_violation_contractor',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.'?pid='.$pid);
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
    public function create(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        $supplyAry  = b_supply::getSelect();
        $parent     = ($request->b_supply_id)? $request->b_supply_id : 0;
        if($parent)
        {
            $projectAry = e_project::getSelect('P',$parent);
            $supplyName = b_supply::getName($parent);
            $memberAry  = b_supply_member::getSelect($parent,1);
            $selectAry  = e_violation::getSelect();
        }
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        $hrefPost   = ($parent)? $this->routerPost1 : $this->routerPost2;
        $btnSubmit  = ($parent)? Lang::get('sys_btn.btn_7') : Lang::get('sys_btn.btn_37');

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($hrefPost,-1),'POST',1,TRUE);

        //先選擇 承攬商
        if(!$parent)
        {
            //名稱
            $html = $form->select('b_supply_id',$supplyAry);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_7'),1);
        } else {
            //承攬商
            $html  = $supplyName;
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_7'));
            //工程案件
            $html  = $form->select('e_project_id',$projectAry);
            $html .= $form->hidden('b_supply_id',$parent);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_1'),1);
            //承攬商成員
            $html = $form->select('b_cust_id',[]);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_59'),1);
            //工作許可証
            $html = $form->select('wp_work_id',[]);
            $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_139'));
            //違規事項
            $html = $form->select('e_violation_id',$selectAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_51'),1);
            //違規時間點
            $html  = $form->date('apply_date','',2);
            $html .= $form->time('apply_time','',2);
            $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_60'),1);
        }

        //Submit
        $submitDiv  = $form->submit($btnSubmit,'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $form->boxFoot($submitDiv);

        $out = $form->output();

        //-------------------------------------------//
        //  View -> out
        //-------------------------------------------//
        $content = new ContentLib();
        $content->rowTo($content->box_form($tbTitle, $out,1));
        $contents = $content->output();

        //-------------------------------------------//
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
           $("#apply_date").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW"
            });
            $("#apply_time").timepicker({
                showMeridian: false,
                defaultTime: false,
                timeFormat: "HH:mm"
            })
            $( "#e_project_id" ).change(function() {
                var eid = $("#e_project_id").val();
                $.ajax({
                  type:"GET",
                  url: "'.url('/findEngineering').'",
                  data: { type: 1, eid : eid},
                  cache: false,
                  dataType : "json",
                  success: function(result){
                     $("#b_cust_id option").remove();
                     $("#b_cust_id").append($("<option value=\'\' selected></option>"));
                     $.each(result, function(key, val) {
                        $("#b_cust_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                     });
                  },
                  error: function(result){
                        //alert("ERR");
                  }
                });
                $.ajax({
                  type:"GET",
                  url: "'.url('/findEngineering').'",
                  data: { type: 5, eid : eid},
                  cache: false,
                  dataType : "json",
                  success: function(result){
                     $("#wp_work_id option").remove();
                     $("#wp_work_id").append($("<option value=\'\' selected></option>"));
                     $.each(result, function(key, val) {
                        $("#wp_work_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                     });
                  },
                  error: function(result){
                        //alert("ERR");
                  }
                });
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
     * 下載Excel
     * @return excel
     */
    protected function downExcel()
    {
        $pid   = Session::get($this->hrefMain.'.search.pid',0);
        $aid   = Session::get($this->hrefMain.'.search.aid',0);
        $bid   = Session::get($this->hrefMain.'.search.bid',0);
        $sdate = Session::get($this->hrefMain.'.search.sdate','');
        $edate = Session::get($this->hrefMain.'.search.edate','');
        if(!$pid)
        {
            \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
        }

        $listAry = $this->getApiViolationContractorList([$bid,$aid,$sdate,$edate,$pid,0,0,'']);
        //dd($listAry);
        if(count($listAry))
        {
            $excelAry = $header = [];
            $header[] = Lang::get($this->langText.'.engineering_1');
            $header[] = Lang::get($this->langText.'.engineering_7');
            $header[] = Lang::get($this->langText.'.engineering_14');
            $header[] = Lang::get($this->langText.'.engineering_51');
            $header[] = Lang::get($this->langText.'.engineering_60');
            $header[] = Lang::get($this->langText.'.engineering_52');
            $header[] = Lang::get($this->langText.'.engineering_53');
            $header[] = Lang::get($this->langText.'.engineering_55');
            $header[] = Lang::get($this->langText.'.engineering_56');
            $header[] = Lang::get($this->langText.'.engineering_58');
            $excelAry[] = $header;
            foreach ($listAry as $value)
            {
                $excelAry[] = [$value->project,$value->supply,$value->user,$value->violation_record1,
                               $value->apply_stamp,$value->violation_record4,$value->violation_record2,
                               $value->violation_record3,$value->isControl,$value->limit_edate];
            }
            Excel::create(Lang::get($this->langText.'.excel15'),function($excel) use ($excelAry){
                $excel->sheet('REPORT', function($sheet) use ($excelAry){
                    $sheet->rows($excelAry);
                });
            })->export('xls');
        }
        \Redirect::back()->withErrors(Lang::get('sys_base.base_10122'));
    }

}
