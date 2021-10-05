<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Engineering\TraningTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning_m;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Storage;

class SupplyRPTraningController extends Controller
{
    use TraningTrait,TraningMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | SupplyRPTraningController
    |--------------------------------------------------------------------------
    |
    |  審查 教育訓練報名
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
        $this->hrefHome         = '/';
        $this->hrefMain         = 'rp_etraning';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'rp_etraning/';
        $this->hrefMainNew      = 'new_rp_etraning';
        $this->routerPost       = 'postERPTraning';

        $this->pageTitleMain    = Lang::get($this->langText.'.title18');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list18');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new18');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit18');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_41');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回

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
        $allowProjectAry = ($this->isRootDept)? [] : $this->allowProjectAry;
        //參數
        $out = $js ='';
        $no  = 0;
        $listAry    = [];
        $courseAry  = et_course::getSelect();
        $aproc      = ($this->isRootDept)? 'P' : 'A';
        $today    = SHCSLib::addDay(-7);
        $maxday   = SHCSLib::addDay(7);

        $sid        = $request->sid;
        $tid        = $request->tid;
        $sdate      = $request->sdate;
        $edate      = $request->edate;
        if($request->has('clear'))
        {
            $sid = $tid = 0;
            Session::forget($this->hrefMain.'.search');
        }
        //開課
        if($tid)
        {
            $tid   = SHCSLib::decode($tid);
            Session::put($this->hrefMain.'.search.tid',$tid);
        } else {
            $tid = Session::put($this->hrefMain.'.search.tid',0);
        }
        if(!$sid)
        {
            $sid = Session::get($this->hrefMain.'.search.sid',0);
            if(!is_numeric($sid)) $sid = 0;
        } else {
            Session::put($this->hrefMain.'.search.sid',$sid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->langText.'.search.sdate',$today);
        } else {
            Session::put($this->langText.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->langText.'.search.edate',$maxday);
        } else {
            Session::put($this->langText.'.search.edate',$edate);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
//        dd($tid,0,$allowProjectAry,$aproc);
        //抓取資料
        if(!$tid)
        {
            $listAry = $this->getApiTraningList($sid,0,$sdate,$edate);
            Session::put($this->hrefMain.'.Record',$listAry);
        } else {
            $listAry = $this->getApiTraningMemberMainList($tid,0,$allowProjectAry,$aproc);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        //$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($tid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        } else {
            $html = $form->select('sid',$courseAry,$sid,2,Lang::get($this->langText.'.engineering_42'));
            $html.= $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_44'));
            $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
            $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
            $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
            $form->addRowCnt($html);
            $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1076'),'red',1);
            $form->addRow($html,7,1);
        }
        $form->addHr();

        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        if($tid)
        {
            $heads[] = ['title'=>Lang::get('sys_supply.supply_12')]; //承攬商
            $heads[] = ['title'=>Lang::get('sys_supply.supply_39')]; //件數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //課程
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_43')]; //授課教師
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_44')]; //開課日期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_46')]; //上課時段
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_147')];  //報名截止時間
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_148')]; //報名人數上限
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_153')]; //已報名核可人數
            $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //件數
        }

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                if($tid)
                {
                    $id           = $value->b_supply_id;
                    $name1        = $value->b_supply; //
                    $name2        = $value->amt; //
                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$tid,'pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_21'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->course; //
                    $name2        = $value->teacher; //
                    $name3        = $value->sdate; //
                    $name31       = $value->traning_time; //
                    $name4        = $value->register_day_limit.' '.substr($value->register_time_limit,0,5); //
                    $name5        = $value->register_men_limit ? $value->register_men_limit : Lang::get('sys_base.base_limit_null'); //
                    $name6        = et_traning_m::isExist(0,$id,0,0,['R','O']); //
                    $name7        = et_traning_m::isExist(0,$id,0,0,[$aproc],$allowProjectAry); //

                    //按鈕
                    $btn          = (!$name7)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMain,'','tid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕
                    if(!$name7) $name7 = HtmlLib::Color(Lang::get($this->langText.'.engineering_1036'),'red',1);

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '31'=>[ 'name'=> $name31],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                        '7'=>[ 'name'=> $name7],
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
                        startDate: "today",
                        language: "zh-TW"
                    });
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
        $js  = $contents ='';
        $id  = SHCSLib::decode($urlid);
        $pid = SHCSLib::decode($request->pid);
        $courseAry    = et_course::getSelect();
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
            $A1             = $getData->et_course_id; //
            $A2             = isset($courseAry[$A1])? $courseAry[$A1] : ''; //
            //$A2         = $getData->teacher; //
            $A3             = $getData->sdate; //
            $A31            = $getData->traning_time; //
            $A4             = $getData->register_day_limit.' '.substr($getData->register_time_limit,0,5); //
            $A5             = $getData->register_men_limit ? $getData->register_men_limit : Lang::get('sys_base.base_limit_null'); //
            $traing_amt1    = et_traning_m::getAmt($id);
            $A6             = $getData->register_men_limit ? ($getData->register_men_limit - $traing_amt1) : Lang::get('sys_base.base_limit_null'); //

            $allowProjectAry    = ($this->isRootDept)? [] : $this->allowProjectAry;
            $aproc              = ($this->isRootDept)? 'P' : 'A';
            $A7                 = ($aproc == 'P')? '.engineering_1078' : '.engineering_1077';
            $A8                 = et_traning_m::getAmt($id,0,['R','O']);
            //已經報名成員;
            $mebmer2    = $this->getApiTraningMemberList($id,0,$pid,0,[$aproc]);
        //    dd([$id,$pid,$mebmer2,$this->isRootDept]);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //授課日期
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_37'),1);
        //上課時段
        $html = $A31;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_46'),1);
        //報名截止日
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_147'),1);
        //報名人數上限
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_148'),1);
        //可報名名額
        $html = $A6;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_154'),1);
        //已報名核可人數
        $html = $A8;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_153'),1);
        //說明
        $html = HtmlLib::Color(Lang::get($this->langText.$A7),'red',1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //已經報名
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_21')]; //身分證
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_1')]; //工程案件
        $heads[] = ['title'=>Lang::get('sys_supply.supply_52')]; //進度
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_80')]; //報名申請

        $table->addHead($heads,0);
        if(count($mebmer2))
        {
            $no = 0;
            foreach($mebmer2 as $value)
            {
                $no++;
                $id           = $value->id;
                //$this->isRootDep => True為工安課 False為監造
                if ((!$this->isRootDept && $value->aproc == 'A') || (($this->isRootDept && $value->aproc == 'P'))) {
                    $name0        = $form->checkbox('member[]', $id, '', 'select_box'); //
                }else {
                    $name0 = '';
                }
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->bc_id); //
                $name3        = $value->project; //
                //審核身分為監造時，P改顯示待工安審查
                if (!$this->isRootDept && $value->aproc == 'P') {
                    $name4 = Lang::get($this->langText . '.engineering_1080');
                } else {
                    $name4 = $value->aproc_name; //
                } 
                $name5        = substr($value->apply_date, 0, 19); //

                $tBody[] = ['0'=>[ 'name'=> $name0],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtn','','checkAll()');
        $form->add('nameT1',($checkAllBtn.$table->output()),Lang::get($this->langText.'.engineering_86'));
        unset($table);


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('et_course_id',$A1);
        $submitDiv.= $form->hidden('pid',$pid);
        $submitDiv.= $form->hidden('register_day_limit',$A4);
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
        });
        var clicked = false;
        function checkAll()
        {
            $(".select_box").prop("checked", !clicked);
            clicked = !clicked;
            btn = clicked ? "'.Lang::get('sys_btn.btn_78').'" : "'.Lang::get('sys_btn.btn_77').'";
            $("#checkAllBtn").html(btn);
        }';

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
        $now = date('Y-m-d h:s');

        //2021-08-09審核都不檢查，超過報名截止時間，無法審核同意
        // if($request->register_day_limit <= $now && $request->has('agreeY') && !$this->isRootDept){
        //     return \Redirect::back()
        //         ->withErrors(Lang::get($this->langText.'.engineering_1081'))
        //         ->withInput();
        // }
        //資料不齊全
        if( !$request->id || !$request->member)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        //沒有選擇成員
        elseif(!count($request->member)){
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1017'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $aproc= ($this->isRootDept)? 'R' : 'P';
        }
        $isNew = 0;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        $upAry['member']              = $request->member;
        $upAry['aproc']               = ($request->has('agreeY'))? $aproc : 'B';
        // dd($upAry);
        
        $err_msg = 'base_10105';

        //新增
        if($isNew)
        {
            $ret = 0;//$this->setTraningMemberGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            $ret = $this->setTraningMemberGroup($id,$upAry,$this->b_cust_id);
            $err_msg = 'base_10178';
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_traning_m',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.' . $err_msg);
            //2-2 更新失敗
            return \Redirect::back()->withErrors($msg);
        }
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
