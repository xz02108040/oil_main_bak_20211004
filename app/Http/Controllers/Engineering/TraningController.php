<?php

namespace App\Http\Controllers\Engineering;

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
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_time;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Storage;

class TraningController extends Controller
{
    use TraningTrait,TraningMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TraningController
    |--------------------------------------------------------------------------
    |
    | 教育訓練開課 維護
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
        //讀取選限
        $this->uri              = SHCSLib::getUri($request->route()->uri);
        $this->isWirte          = 'N';
        //路由
        $this->hrefHome         = '/';
        $this->hrefMain         = 'etraning';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'etraning/';
        $this->hrefMainRoster   = 'etraningroster/';
        $this->hrefMainDetail2  = 'etraningtime';
        $this->hrefMainDetail3  = 'etraningmember';
        $this->hrefMainNew      = 'new_etraning';
        $this->routerPost       = 'postETraning';

        $this->pageTitleMain    = Lang::get($this->langText.'.title13');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list13');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new13');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit13');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_76');//[按鈕]新增
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js ='';
        $today  = date('Y-m-d');
        $maxday = SHCSLib::addDay(+30);
        $no  = 0;
        $listAry    = [];
        $closeAry   = SHCSLib::getCode('CLOSE',1);
        $courseAry  = et_course::getSelect();
        $cid    = $request->cid;
        $tid    = $request->tid;
        $sdate  = $request->sdate;
        $edate  = $request->edate;
        $close  = $request->close;
        if($request->has('clear'))
        {
            $cid = $tid = $sdate = $edate = $close = '';
            Session::forget($this->langText.'.search');
        }
        if(!$cid)
        {
            $cid = Session::get($this->langText.'.search.cid',1);
        } else {
            Session::put($this->langText.'.search.cid',$cid);
        }
        if(!$tid)
        {
            $tid = Session::get($this->langText.'.search.tid','');
        } else {
            Session::put($this->langText.'.search.tid',$tid);
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
        if(!$close)
        {
            $close = Session::get($this->langText.'.search.close','N');
        } else {
            Session::put($this->langText.'.search.close',$close);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if($cid)
        {
            $listAry = $this->getApiTraningList($cid,$tid,$sdate,$edate,$close);
            Session::put($this->hrefMain.'.Search',[$cid,$tid,$sdate,$edate,$close]);
            Session::put($this->hrefMain.'.Record',$listAry);
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y')$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        $html = $form->select('cid',$courseAry,$cid,2,Lang::get($this->langText.'.engineering_42'));
        $html.= $form->text('tid',$tid,2,Lang::get($this->langText.'.engineering_43'));
        $html.= $form->select('close',$closeAry,$close,2,Lang::get($this->langText.'.engineering_33'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.engineering_44'));
        $html.= $form->date('edate',$edate,2,Lang::get($this->langText.'.engineering_9'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.engineering_1028'),'red',1);
        $form->addRow($html,4,1);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_39')]; //開課代碼
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //課程
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_43')]; //授課教師
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_44')]; //有效日期
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_46')]; //上課時段
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_149')]; //報名截止日
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_147')]; //報名截止時間
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_148')]; //報名人數上限
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_157')]; //監造審查中人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_158')]; //工安審查中人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_162')]; //報名不通過人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_153')]; //已報名核可人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_156')]; //訓練通過人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_163')]; //訓練不通過人數
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_87')]; //上課成員

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->course; //
                $name2        = $value->teacher; //
                $name3        = $value->sdate; //
                $name4        = $value->traning_time; //
                $name6        = $value->course_no; //
                $name7        = $value->register_day_limit; //
                $name8        = substr($value->register_time_limit,0,5); //
                $name5        = $value->register_men_limit ? $value->register_men_limit : Lang::get('sys_base.base_limit_null'); //
                $name11       = $value->traning_men1; //
                $name12       = $value->traning_men2; //
                $name13       = $value->traning_men3; //
                $name14       = $value->traning_men4; //
                $name15       = $value->traning_men5; //
                $name16       = $value->traning_men6; //

                //按鈕
                //$LicenseBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_36'),3); //按鈕
                $MemberBtn    = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_30'),4); //按鈕

                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕
                $btn         .= ($name13)? HtmlLib::btn(SHCSLib::url($this->hrefMainRoster,$id),Lang::get('sys_btn.btn_52'),2,'','','','_blank') : ''; //按鈕

                $tBody[] = [
                            '6'=>[ 'name'=> $name6],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '11'=>[ 'name'=> $name7],
                            '12'=>[ 'name'=> $name8],
                            '13'=>[ 'name'=> $name5],
                            '21'=>[ 'name'=> $name11],
                            '22'=>[ 'name'=> $name12],
                            '25'=>[ 'name'=> $name15],
                            '23'=>[ 'name'=> $name13],
                            '24'=>[ 'name'=> $name14],
                            '26'=>[ 'name'=> $name16],
                            '13'=>[ 'name'=> $name5],
                            '98'=>[ 'name'=> $MemberBtn],
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
                    $("#sdate,#edate").datepicker({
                        format: "yyyy-mm-dd",
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
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
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->et_course_id; //
            $A2         = $getData->teacher; //
            $A3         = $getData->sdate; //
            $A4         = $getData->edate; //
            $A5         = $getData->traning_time; //
            $A6         = $getData->course_no; //
            $A7         = $getData->course; //
            $A13        = $getData->memo; //
            $A14        = $getData->register_day_limit; //
            $A15        = substr($getData->register_time_limit,0,8); //
            $A16        = $getData->register_men_limit; //

            $isEdit     = (strtotime($A4) < time())? 0 : 1;

            $A97        = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A7;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //課程
        $html = ($isEdit)? $form->text('course_no',$A6) : $A6;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_39'),1);
        //老師
        $html = ($isEdit)? $form->text('teacher',$A2) : $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_43'),1);
        //開始日期
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_44'),1);
        //結束日期
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //上課時段
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_46'),1);
        //報名截止日期
        $html = ($isEdit)? $form->date('register_day',$A14,2,'','','',$A4) : $A14;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_149'),1);
        //報名時間
        $html = ($isEdit)? $form->time('register_time',$A15,2) : $A15;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_147'),1);
        //報名人數
        $html = ($isEdit)? $form->number('register_men',$A16,2,0,9999) : $A16;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_148'),1);
        //說明
        $html = ($isEdit)? $form->textarea('memo',$A13) : $A13;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));
        //停用
        if($isEdit)
        {
            $html = $form->checkbox('isClose','Y',$A99);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.engineering_34'));
        }
        if($A99)
        {
            $html = $A97;
            $form->add('nameT98',$html,Lang::get('sys_base.base_10615'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = ($isEdit)? $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;' : '';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('et_course_id',$A1);
        $submitDiv.= $form->hidden('sdate',$A3);
        $submitDiv.= $form->hidden('edate',$A4);
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
            $("#register_day").datepicker({
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
     * 新增/更新資料
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        $this->getBcustParam();
        $id = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        $isErrCnt = $isExistCnt = 0;
        //資料不齊全
        if( !$request->has('agreeY') || !$request->id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }

        if($isNew)
        {
            if(!$request->course_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1064'))
                    ->withInput();
            }
            if(!$request->et_traning_time_id)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1057'))
                    ->withInput();
            }
            if(!$request->teacher)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1061'))
                    ->withInput();
            }
            if(!CheckLib::isDate($request->sdate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1062'))
                    ->withInput();
            }
            if(!CheckLib::isDate($request->edate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1063'))
                    ->withInput();
            }
            //開始日期不可大於結束日期
            if(strtotime($request->sdate) > strtotime($request->edate)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1002'))
                    ->withInput();
            }
            //不可小於今日
            elseif(strtotime($request->edate) < strtotime(date('Y-m-d'))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1003'))
                    ->withInput();
            }
            if(!$request->register_day)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1058'))
                    ->withInput();
            }
            if(!$request->register_time)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1059'))
                    ->withInput();
            }
        } elseif($request->isClose != 'Y') {
            if(et_traning::isCourseNoExist($request->course_no,SHCSLib::decode($request->id))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1007'))
                    ->withInput();
            }
            //同一個課程不得重複開立
            elseif(et_traning::isExist($request->course_id,$request->sdate,$request->edate,$request->et_traning_time_id,SHCSLib::decode($request->id))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1006'))
                    ->withInput();
            }
            elseif(!CheckLib::isDate($request->register_day))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1065'))
                    ->withInput();
            }
            elseif(strtotime($request->register_day) > strtotime($request->edate))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.engineering_1066'))
                    ->withInput();
            }
        }


        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }else {
            $upAry['et_course_id']       = is_numeric($request->course_id) ? $request->course_id : 0;
            $upAry['sdate']              = $request->sdate;
            $upAry['edate']              = $request->edate;
            $upAry['et_traning_time_id'] = isset($request->et_traning_time_id)? $request->et_traning_time_id : 0;
            $upAry['week']               = $upAry['et_traning_time_id']? et_traning_time::getWeek($upAry['et_traning_time_id']) : 0;
        }
        $upAry['course_no']          = isset($request->course_no) ?     $request->course_no : '';
        $upAry['teacher']            = strlen($request->teacher)? $request->teacher : '';
        $upAry['register_day']       = isset($request->register_day)? $request->register_day : 1;
        $upAry['register_time']      = isset($request->register_time)? date("H:i", strtotime($request->register_time)) : '12:00';
        $upAry['register_men']       = isset($request->register_men)? $request->register_men : 0;
        $upAry['memo']               = isset($request->memo)? $request->memo : '';
        $upAry['isClose']            = ($request->isClose == 'Y')? 'Y' : 'N';
       
        //新增
        if($isNew)
        {
            [$ret,$isErrCnt,$isExistCnt] = $this->createCyclicTraning($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setTraning($id,$upAry,$this->b_cust_id);
        }
        //2-1. 更新成功
        if($ret || $isExistCnt)
        {
            //沒有可更新之資料
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //動作紀錄
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'et_traning',$id);
                //2-1-2 回報 更新成功
                if($isNew)
                {
                    $msgColor = ($isExistCnt)? 'errors' : 'message';
                    Session::flash('message',Lang::get('sys_base.base_10171',['amt1'=>$id,'amt2'=>$isErrCnt,'amt3'=>$isExistCnt]));
                } else {
                    Session::flash('message',Lang::get('sys_base.base_10104'));
                }
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        if($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        }
        //參數
        $js = $contents = '';
        $courseAry  = et_course::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //課程
        $html = $form->select('course_id',$courseAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //老師
        $html = $form->text('teacher');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_43'),1);
        //開始日期
        $html = $form->date('sdate');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_44'),1);
        //結束日期
        $html = $form->date('edate');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_9'),1);
        //星期
        $html = $form->select('et_traning_time_id',[],1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_144'),1);
        //報名限制可提前天數
        $TRANING_REGISTER_DAY_MAX = sys_param::getParam('TRANING_REGISTER_DAY_MAX',15);
        $html = $form->number('register_day',$TRANING_REGISTER_DAY_MAX,2,1,$TRANING_REGISTER_DAY_MAX);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_146'),1);
        //報名時間限制
        $TRANING_REGISTER_TIME_MAX = sys_param::getParam('TRANING_REGISTER_TIME_MAX','12:00');
        $html = $form->time('register_time',$TRANING_REGISTER_TIME_MAX,2);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_147'),1);
        //報名人數限制
        $TRANING_REGISTER_MEN_MAX = sys_param::getParam('TRANING_REGISTER_MEN_MAX',0);
        $html = $form->number('register_men',$TRANING_REGISTER_MEN_MAX,2,0,9999);
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_148'),1);
        //說明
        $html = Lang::get($this->langText.'.engineering_145').'<br/>'.$form->textarea('memo');
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
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
            var cid = $("#course_id").val();
            if(cid) chg(cid);
        
           $("#sdate,#edate").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW",
                orientation: "bottom"
            });
            $( "#course_id" ).change(function() {
                        var cid = $("#course_id").val();
                        chg(cid);
             });
        });
        function chg(cid)
        {
            $.ajax({
                          type:"GET",
                          url: "'.url('/findCourse').'",  
                          data: { type: 2, cid : cid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#et_traning_time_id option").remove();
                             $.each(result, function(key, val) {
                                $("#et_traning_time_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
        }
        ';

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

    protected function report(Request $request,$urlid)
    {
        $showAry        = $pageAry = [];
        $max_show_mun   = 10;
        $page           = 0;

        $traning_id = SHCSLib::decode($urlid);
        $listAry   = $this->getApiTraningMemberList3($traning_id);
        //title
        foreach ($listAry as $projectVal)
        {
            foreach ($projectVal as $memberVal)
            {
                $i = 0;$no = 1;
                $page++;
                $memberAry = [];
                $totalAmt = count($memberVal);
                $modNum   = $totalAmt % $max_show_mun;
                if(!$totalAmt) $modNum = $max_show_mun;
                if($totalAmt && $modNum && $modNum != $max_show_mun) $modNum = $max_show_mun - $modNum;
                if($totalAmt)
                {
                    foreach ($memberVal as $val) {
                        if (!isset($pageAry[$page]['supply_name'])) {
                            $pageAry[$page]['project_no'] = isset($val['project_no']) ? $val['project_no'] : '';
                            $pageAry[$page]['supply_name'] = isset($val['supply']) ? $val['supply'] : '';
                            $pageAry[$page]['supply_no'] = isset($val['tax_num']) ? $val['tax_num'] : '';
                            $pageAry[$page]['supply_boss'] = isset($val['boss_name']) ? $val['boss_name'] : '';
                            $pageAry[$page]['supply_tel'] = isset($val['tel1']) ? $val['tel1'] : '';
                            $pageAry[$page]['supply_fax'] = isset($val['fax1']) ? $val['fax1'] : '';
                            $pageAry[$page]['supply_addr'] = isset($val['address']) ? $val['address'] : '';
                        }


                        $tmp = [];
                        $tmp['no'] = $no;
                        $tmp['id'] = isset($val['b_cust_id']) ? $val['b_cust_id'] : '';
                        $tmp['name'] = isset($val['name']) ? $val['name'] : '';
                        $tmp['bcid'] = isset($val['bc_id']) ? $val['bc_id'] : '';
                        $tmp['bcid1'] = ($tmp['bcid']) ? substr($tmp['bcid'], 0, 1) : '';
                        $tmp['bcid2'] = ($tmp['bcid']) ? substr($tmp['bcid'], 1, 1) : '';
                        $tmp['bcid3'] = ($tmp['bcid']) ? substr($tmp['bcid'], 2, 1) : '';
                        $tmp['bcid4'] = ($tmp['bcid']) ? substr($tmp['bcid'], 3, 1) : '';
                        $tmp['bcid5'] = ($tmp['bcid']) ? substr($tmp['bcid'], 4, 1) : '';
                        $tmp['bcid6'] = ($tmp['bcid']) ? substr($tmp['bcid'], 5, 1) : '';
                        $tmp['bcid7'] = ($tmp['bcid']) ? substr($tmp['bcid'], 6, 1) : '';
                        $tmp['bcid8'] = ($tmp['bcid']) ? substr($tmp['bcid'], 7, 1) : '';
                        $tmp['bcid9'] = ($tmp['bcid']) ? substr($tmp['bcid'], 8, 1) : '';
                        $tmp['bcid10'] = ($tmp['bcid']) ? substr($tmp['bcid'], 9, 1) : '';
                        $tmp['traning_date_y'] = isset($val['traning_date']) ? substr($val['traning_date'],0,4) : ''; //取出年
                        $tmp['traning_date_m'] = isset($val['traning_date']) ? substr($val['traning_date'],5,2) : ''; //取出月
                        $tmp['traning_date_d'] = isset($val['traning_date']) ? substr($val['traning_date'],8,2) : ''; //取出日
                        $tmp['traning_time'] = isset($val['traning_time']) ? $val['traning_time'] : '';

                        $no++;
                        $i++;
                        $pageAry[$page]['member'][] = $tmp;
                        if ($max_show_mun == $i) {
                            $page++;
                            $i = 0;
                        }
                    }
                }
                for($j = 1; $j < $modNum; $j++)
                {
                    $tmp = [];
                    $tmp['no']          = $no;
                    $tmp['id']          = '';
                    $tmp['name']        = '';
                    $tmp['bcid']        = '';
                    $tmp['bcid1']       = '';
                    $tmp['bcid2']       = '';
                    $tmp['bcid3']       = '';
                    $tmp['bcid4']       = '';
                    $tmp['bcid5']       = '';
                    $tmp['bcid6']       = '';
                    $tmp['bcid7']       = '';
                    $tmp['bcid8']       = '';
                    $tmp['bcid9']       = '';
                    $tmp['bcid10']      = '';
                    $tmp['traning_date_y']= '';
                    $tmp['traning_date_m']= '';
                    $tmp['traning_date_d']= '';
                    $tmp['traning_time']= '';
                    $no++;

                    $pageAry[$page]['member'][] = $tmp;
                }
            //    dd($val, $tmp, $pageAry);
            }
        }
//        dd($listAry,$pageAry);

        $showAry['totalPage'] = count($pageAry);
        $showAry['pageAry'] = $pageAry;
        if($request->showtest) dd($pageAry);
        return view('report.cpc_roster1',$showAry);
    }
}
