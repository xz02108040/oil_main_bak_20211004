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

class TraningMemberImportController extends Controller
{
    use TraningTrait,TraningMemberTrait,SessTraits,DoorTrait;
    /*
    |--------------------------------------------------------------------------
    | TraningMemberImportController
    |--------------------------------------------------------------------------
    |
    | 外廠教育訓練紀錄 加入
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
        $this->hrefHome         = 'etraning';
        $this->hrefMain         = 'exa_etraningmember2';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'exa_etraningmember2/';
        $this->hrefMainNew      = 'new_etraningmember2';
        $this->routerPost       = 'postExaETraningmember2';
        $this->routerPost2      = 'exaetraningmemberList2';

        $this->pageTitleMain    = Lang::get($this->langText.'.title28');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list28');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new28');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit28');//編輯

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
        //參數
        $out = $js ='';
        $no  = 0;
        $sdate    = date('Y-m-d');
        //開課
        $cid      = $request->cid;
        $tid      = $request->tid;
        if($cid)
        {
            $cid   = SHCSLib::decode($cid);
            Session::put($this->hrefMain.'.search.cid',$cid);
        }
        if($tid)
        {
            $tid   = SHCSLib::decode($tid);
            $cid   = Session::get($this->hrefMain.'.search.cid');
            Session::put($this->hrefMain.'.search.tid',$tid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $cname    = ($cid)? $Icon.et_course::getName($cid) : '';
        $cparam   = ($cid)? '?cid='.SHCSLib::encode($cid) : '';
        $tbTitle  = $this->pageTitleList.$cname;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        if(!$cid)
        {
            $listAry = $this->getApiTraningCourseList(0,$sdate);
            Session::put($this->hrefMain.'.Record',$listAry);
        }elseif(!$tid)
        {
            $listAry = $this->getApiTraningList($cid,0,$sdate);
            Session::put($this->hrefMain.'.Record',$listAry);
        } else {
            $listAry = $this->getApiTraningMemberMainList($tid,'P');
        }

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($tid)
        {
            $form->addLinkBtn($hrefBack.$cparam, $btnBack,1); //返回
            $form->addHr();
        }
        if($cid && !$tid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
            $form->addHr();
        }
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        if($tid)
        {
            $heads[] = ['title'=>Lang::get('sys_supply.supply_12')]; //承攬商
        }elseif($cid) {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_44')]; //有效日期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_9')];  //結束日期
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_45')]; //有效天數
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_42')]; //課程
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_43')]; //授課教師
        }
        if($cid)
        {
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
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$tid,'pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> $btn ]
                    ];
                }elseif($cid) {
                    $id           = $value->id;
                    $name3        = $value->sdate; //
                    $name4        = $value->edate; //
                    $name5        = $value->valid_day; //
                    $name6        = et_traning_m::isExist(0,$id,0,0,['P']); //

                    //按鈕
                    $btn          = (!$name6)? '' : HtmlLib::btn(SHCSLib::url($this->hrefMain,'','tid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕
                    if(!$name6) $name6 = HtmlLib::Color(Lang::get($this->langText.'.engineering_1036'),'red',1);

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '5'=>[ 'name'=> $name5],
                        '6'=>[ 'name'=> $name6],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->et_course_id;
                    $name1        = $value->course; //
                    $name2        = $value->teacher; //
                    //$name3        = et_traning_m::isExist($id,0,0,0,'P'); //

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','cid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
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
     * 資料 列印
     */
    public function print(Request $request)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $contents = $out = '';
        $no     = $pageRow = 0;
        $totalPage = 1;
        $tbAry  = [];
        $pid    = Session::get($this->hrefMain.'.traning_id',0);
        if(!$pid || !is_numeric($pid))
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        } else {
            $course = et_traning::getName($pid);
        }
        //view元件參數
        $pdf_row_max_amt = 10;
        $tbTitle = Lang::get($this->langText.'.pdf18',['head'=>$course]); //header
        $tbDate  = Lang::get($this->langText.'.engineering_92'); //簽到日期

        //-------------------------------------------//
        //  資料內容
        //-------------------------------------------//
        $listAry = $this->getApiTraningMemberList($pid,0,0,0,['P']);
        if($total = count($listAry))
        {
            foreach($listAry as $value)
            {
                $pageRow++;
                $tmp = [];
                $tmp['supply']= $value->supply;
                $tmp['name']  = $value->name;
                $tmp['bcid']  = SHCSLib::genBCID($value->bc_id);

                $tbAry[$totalPage][$pageRow] = $tmp;
                if($pageRow >= $pdf_row_max_amt) {
                    $totalPage++;
                    $pageRow = 0;
                }
            }
            $totalPage = ($total % $pdf_row_max_amt)? $totalPage : $totalPage -1;
        }
        //dd($tbAry);
        //-------------------------------------------//
        //  產生 ＰＤＦ內容
        //-------------------------------------------//

        foreach ($tbAry as $page => $cntTb)
        {
            $heads = $tBody = [];
            //標題
            $out .= '<div class="page-break">';
            $out .= '<h4 class="text-center">'.$tbTitle.'</h4>';
            $out .= '<p class="text-right">'.$tbDate.'</p>';
            //table
            $table = new TableLib();
            //標題
            $heads[] = ['title'=>'NO'];
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_88')]; //公司
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_89')]; //學員
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_90')]; //身分證
            $heads[] = ['title'=>Lang::get($this->langText.'.engineering_91')]; //報名申請

            $table->addHead($heads,0);
            foreach ($cntTb as $val)
            {
                $no++;
                $name1        = $val['supply']; //
                $name2        = $val['name']; //
                $name3        = $val['bcid']; //

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1,'style'=>'width:15%;'],
                            '2'=>[ 'name'=> $name2,'style'=>'width:15%;'],
                            '3'=>[ 'name'=> $name3,'style'=>'width:15%;'],
                            '4'=>[ 'name'=> '      '],
                ];
            }
            $table->addBody($tBody);
            //dd($tBody);
            //輸出
            $out .= $table->output();
            $out .= '<p class="text-right">'.$page.'/'.$totalPage.'</p>';
            $out .= '</div>';
            unset($table,$heads,$tBody);
        }

        //-------------------------------------------//
        //  顯示內容
        //-------------------------------------------//
        $contents = $out;
        //-------------------------------------------//
        //  回傳
        //-------------------------------------------//
        $retArray = ['content'=>$contents];
//        $pdf = PDF::loadView('print', $retArray);
//        return $pdf->download('invoice.pdf');
        return view('print',$retArray);
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
            $A3         = $getData->sdate; //
            $A4         = $getData->edate; //
            $A5         = $getData->valid_day; //
            $A13        = $getData->memo; //

            //已經報名成員
            $mebmer2    = $this->getApiTraningMemberList($id,0,$pid,0,['P']);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.engineering_42'),1);
        //授課區間
        $html = $A3.' - '.$A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_37'),1);
        //說明
        //$html = $A13;
        //$form->add('nameT1', $html,Lang::get($this->langText.'.engineering_13'));

        //已經報名
        $table = new TableLib();
        //標題
        $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
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
                $name0        = $form->checkbox('member[]',$id); //
                $name1        = $value->name; //
                $name2        = $value->bc_id; //
                $name3        = $value->aproc_name; //
                $name4        = $value->apply_date; //

                $tBody[] = ['0'=>[ 'name'=> $name0],
                    '1'=>[ 'name'=> $name1],
                    '2'=>[ 'name'=> $name2],
                    '3'=>[ 'name'=> $name3],
                    '4'=>[ 'name'=> $name4],
                ];
            }
            $table->addBody($tBody);
        }
        //輸出
        $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_86'));
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
        $isNew  = 1;
        $action = ($isNew)? 1 : 2;
        //資料不齊全
        if( !$request->id || !$request->member || !$request->et_course_id || !$request->b_supply_id)
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
        //請填寫【受訓日期】
        elseif(!CheckLib::isDate($request->traning_date))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1068'))
                ->withInput();
        }
        //受訓日期不可為未來
        elseif($request->traning_date && strtotime($request->traning_date) > time())
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1067'))
                ->withInput();
        }

        $upAry = array();
        $upAry['member']              = $request->member;
        $upAry['et_course_id']        = $request->et_course_id;
        $upAry['et_traning_id']       = $request->et_traning_id;
        $upAry['b_supply_id']         = $request->b_supply_id;
        $upAry['traning_date']        = $request->traning_date;
        $upAry['traning_unit']        = $request->traning_unit;
        $upAry['aproc']               = 'O';
        $upAry['isExcel']             = 'Y';
        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createTraningMemberGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            //$ret = $this->setTraningMemberGroup($id,$upAry,$this->b_cust_id);
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
        //承攬商
        $supplyAry  = b_supply::getSelect();
        $courseAry  = et_course::getSelect();
        $tid        = 0;
        //POST
        $sid        = $request->b_supply_id;
        $cid        = $request->et_course_id;
        $tdate      = $request->traning_date;
        $unit       = $request->traning_unit;
        $postRoute  = ($sid && $cid && $tdate)? $this->routerPost : $this->routerPost2;
        $postSubmit = ($sid && $cid && $tdate)? 'btn_7' : 'btn_37';
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header
        //
        //受訓日期不可為未來
        if($request->traning_date && strtotime($request->traning_date) > time())
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.engineering_1067'))
                ->withInput();
        }

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($postRoute,-1) ,'POST',1,TRUE);
        //教育訓練
        if($cid)
        {
            $html  = $form->hidden('et_course_id',$cid);
            $html .= isset($courseAry[$cid])? $courseAry[$cid] : '';
        } else {
            $html = $form->select('et_course_id',$courseAry);
        }
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_41'),1);
        //承攬商
        if($sid)
        {
            $html  = $form->hidden('b_supply_id',$sid);
            $html .= b_supply::getName($sid);
        } else {
            $html = $form->select('b_supply_id',$supplyAry);
        }
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_93'),1);
        //受訓日期
        if($tdate)
        {
            $html  = $tdate.$form->hidden('traning_date',$tdate);
        } else {
            $html = $form->date('traning_date');
        }
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_150'),1);
        //受訓單位
        if($unit)
        {
            $html  = $unit.$form->hidden('traning_unit',$unit);
        } else {
            $html = $form->text('traning_unit',Lang::get($this->langText.'.engineering_143'));
        }
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_142'));

        if($cid && $tdate && $sid)
        {
            $tid        = et_traning::getForeverTraning($cid);
            //承攬商成員
            $mebmer1    = b_supply_member::getSelect($sid,1,'',0);
            //已經報名成員
            $mebmer2    = $this->getApiTraningMemberList($tid,$cid,$sid,0,['O']);
//            dd($mebmer2);
//            foreach ($mebmer2 as $value)
//            {
//                if(isset($mebmer1[$value->b_cust_id]))
//                {
//                    unset($mebmer1[$value->b_cust_id]);
//                }
//            }
            //報名
            $table = new TableLib();
            //標題
            $heads[] = ['title'=>Lang::get('sys_supply.supply_43')]; //
            $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
            $heads[] = ['title'=>Lang::get('sys_supply.supply_37')]; //成員

            $table->addHead($heads,0);
            if(count($mebmer1))
            {
                foreach($mebmer1 as $id => $value)
                {
                    $name1        = $form->checkbox('member[]',$id); //
                    $name2        = $value; //
                    $sdate        = isset($mebmer2[$id])? $mebmer2[$id]['pass_date'] : ''; //
                    $edate        = isset($mebmer2[$id])? $mebmer2[$id]['valid_date'] : ''; //
                    $name3        = ($sdate)? HtmlLib::Color(Lang::get($this->langText.'.engineering_166',['sdate'=>$sdate,'edate'=>$edate]),'red',1) : '';

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                                '1'=>[ 'name'=> $name2],
                                '2'=>[ 'name'=> $name3],
                    ];
                }
                $table->addBody($tBody);
            }
            //輸出
            $form->add('nameT1', $table->output(),Lang::get($this->langText.'.engineering_141'));
            unset($table,$heads,$tBody);
        }

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.'.$postSubmit),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('et_traning_id',$tid);
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
           $("#traning_date").datepicker({
                format: "yyyy-mm-dd",
                language: "zh-TW",
                orientation: "bottom"
            });
           $( "#et_course_id" ).change(function() {
                        var cid  = $(this).val();
                        //chgTraningSelect(cid);
             });
        });
        function chgTraningSelect(cid)
        {
            $.ajax({
                          type:"GET",
                          url: "'.url('/findCourse').'",  
                          data: { type: 1, cid : cid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#et_traning_id option").remove();
                             $.each(result, function(key, val) {
                                $("#et_traning_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                //alert("ERR");
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

}
