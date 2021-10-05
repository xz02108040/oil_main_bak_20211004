<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Engineering\TraningTrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_m;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use PDF;

class TraningMemberExaController extends Controller
{
    use TraningTrait,TraningMemberTrait,SessTraits,DoorTrait;
    /*
    |--------------------------------------------------------------------------
    | TraningMemberExaController
    |--------------------------------------------------------------------------
    |
    | 開課學員 審查
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
        $this->hrefHome         = 'etraning';
        $this->hrefMain         = 'exa_etraningmember';
        $this->hrefPDF          = 'exa_etraningmemberorder';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'exa_etraningmember/';
        $this->hrefMainNew      = 'new_etraningmember';
        $this->routerPost       = 'postExaETraningmember';

        $this->pageTitleMain    = Lang::get($this->langText.'.title19');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list19');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new19');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit19');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pagePrintBtn     = Lang::get('sys_btn.btn_42');//[按鈕]列印

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
        $today    = SHCSLib::addDay(-7);
        $maxday   = date('Y-m-d');
        $courseAry= et_course::getSelect();
        //開課
        $cid      = $request->cid;
        $tid      = $request->tid;
        $sdate  = $request->sdate;
        $edate  = $request->edate;
        if($request->has('clear'))
        {
            $cid = $tid = 0;
            $sdate = $sdate = '';
            Session::forget($this->hrefMain.'.search');
        }
        if($cid)
        {
            $cid   = $cid;
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if($tid)
        {
            $tid   = SHCSLib::decode($tid);
            $cid   = SHCSLib::decode(Session::get($this->hrefMain.'.search.cid'));
            Session::put($this->hrefMain.'.search.tid',$tid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',$today);
        } else {
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$edate)
        {
            $edate = Session::get($this->hrefMain.'.search.edate',$maxday);
        } else {
            Session::put($this->hrefMain.'.search.edate',$edate);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $cname    = ($cid)? $Icon.et_course::getName($cid) : '';
        // $cparam   = ($cid)? '?cid='.SHCSLib::encode($cid) : '';
        //2021-07-28 返回主頁$cid會有加密問題，先以不暫存方式調整
        $cparam   = '';
        
        $tbTitle  = $this->pageTitleList.$cname;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$tid)
        {
            $listAry = $this->getApiTraningList($cid,0,$sdate,$edate);
            Session::put($this->hrefMain.'.Record',$listAry);
        } else {
            $listAry = $this->getApiTraningMemberMainList($tid,0,$allowProjectAry,'R');
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($tid)
        {
            // dd($cparam, $btnBack);
            $form->addLinkBtn($hrefBack.$cparam, $btnBack,1); //返回
            $form->addHr();
        }
        $html = $form->select('cid',$courseAry,$cid,2,Lang::get($this->langText.'.engineering_42'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_44'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
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
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_155')]; //報名尚未核可人數
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_156')]; //訓練通過人數
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_163')]; //訓練通過人數
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_164')]; //訓練通過人數
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

                    $tBody[] = [
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->course; //
                    $name2        = $value->teacher; //
                    $name3        = $value->sdate; //
                    $name31        = $value->traning_time; //
                    $name4        = $value->register_day_limit.' '.substr($value->register_time_limit,0,5); //
                    $name5        = $value->register_men_limit ? $value->register_men_limit : Lang::get('sys_base.base_limit_null'); //
                    $name6        = et_traning_m::isExist(0,$id,0,0,['A','P']); //
                    $name7        = et_traning_m::isExist(0,$id,0,0,['O']); //
                    $name8        = et_traning_m::isExist(0,$id,0,0,['R']); //
                    $name9        = et_traning_m::isExist(0,$id,0,0,['C']); //
                    //按鈕
                    $btn          = (!$name8)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMain,'','tid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = [
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '31'=>[ 'name'=> $name31],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                        '7'=>[ 'name'=> $name7],
                        '9'=>[ 'name'=> $name9],
                        '8'=>[ 'name'=> $name8],
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
            $A1         = $getData->et_course_id; //
            $A2         = isset($courseAry[$A1])? $courseAry[$A1] : ''; //
            //$A2         = $getData->teacher; //
            $A3             = $getData->sdate; //
            $A31            = $getData->traning_time; //
            $A4             = $getData->register_day_limit.' '.substr($getData->register_time_limit,0,5); //
            $A5             = $getData->register_men_limit ? $getData->register_men_limit : Lang::get('sys_base.base_limit_null'); //
            $A6             = et_traning_m::getAmt($id,0,['R']);
            $A7             = et_traning_m::getAmt($id,0,['O']);

            //已經報名成員
            $mebmer2    = $this->getApiTraningMemberList($id,0,$pid,0,['R']);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //開課期間
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_37'),1);
        //授課區間
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
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_153'),1);
        //訓練通過人數
        $html = $A7;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_156'),1);
        //說明
        //$html = $A13;
        //$form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //已經報名
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
        $heads[] = ['title'=>Lang::get('sys_supply.supply_12')]; //
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_21')]; //身分證
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
                $name0        = $form->checkbox('member[]',$id,'','select_box'); //
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->bc_id); //
                $name3        = $value->aproc_name; //
                $name4        = $value->apply_date; //
                $name5        = $value->supply; //

                $tBody[] = ['0'=>[ 'name'=> $name0],
                    '5'=>[ 'name'=> $name5],
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtn','','checkAll()');
        $form->add('nameT1', $checkAllBtn.$table->output(),Lang::get($this->langText.'.engineering_86'));
        unset($table);


        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('et_course_id',$A1);
        $submitDiv.= $form->hidden('pid',$pid);
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
        }
        $isNew = 0;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        $upAry['member']              = $request->member;
        $upAry['aproc']               = ($request->has('agreeY'))? 'O' : 'C';
        $isOK                         = ($request->has('agreeY'))? 1   : 0;
        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret = 0;//$this->setTraningMemberGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setTraningMemberGroup($id,$upAry,$this->b_cust_id);
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
                if($isOK)
                {
                    //新增 教育訓練資格 通過名單紀錄
                    //$this->createCoursePass();
                }
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_traning_m',$id);

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
     * 單筆資料 新增
     */
    public function create(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents = '';
        $pid    = Session::get($this->hrefMain.'.traning_id',0);
        $urlid  = SHCSLib::encode($pid);
        if(!$pid)
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        }
        //承攬商
        $supplyAry  = b_supply::getSelect();
        $sid        = $request->b_supply_id;
        $postRoute  = ($sid)? $this->routerPost : $this->routerPost2;
        $postSubmit = ($sid)? 'btn_41' : 'btn_37';
        //view元件參數
        $hrefBack   = $this->hrefMain.'?pid='.$urlid;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($postRoute,-1) ,'POST',1,TRUE);
        //承攬商
        if($sid)
        {
            $html  = $form->hidden('b_supply_id',$sid);
            $html .= b_supply::getName($sid);
        } else {
            $html = $form->select('b_supply_id',$supplyAry);
        }
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_93'),1);

        if($sid)
        {
            //承攬商成員
            $mebmer1    = b_supply_member::getSelect($sid,1,'',0);
            //已經報名成員
            $mebmer2    = $this->getApiTraningMemberList($pid,0,$sid);
            foreach ($mebmer2 as $value)
            {
                if(isset($mebmer1[$value->b_cust_id]))
                {
                    unset($mebmer1[$value->b_cust_id]);
                }
            }
            //報名
            $table = new TableLib();
            //標題
            $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
            $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員

            $table->addHead($heads,0);
            if(count($mebmer1))
            {
                foreach($mebmer1 as $id => $value)
                {
                    $name1        = $form->checkbox('member[]',$id); //
                    $name2        = $value; //

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                        '1'=>[ 'name'=> $name2],
                    ];
                }
                $table->addBody($tBody);
            }
            //輸出
            $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_85'));
            unset($table,$heads,$tBody);
        }

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$postSubmit),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('pid',$urlid);
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
           $("#stime,#etime").timepicker({
                showMeridian: false,
                defaultTime: false
            })
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
