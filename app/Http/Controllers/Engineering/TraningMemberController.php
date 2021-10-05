<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\SessTraits;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_m;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_supply_etraning;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use PDF;

class TraningMemberController extends Controller
{
    use TraningMemberTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | TraningMemberController
    |--------------------------------------------------------------------------
    |
    | 開課報名 維護
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
        $this->hrefMain         = 'etraningmember';
        $this->hrefPDF          = 'etraningroster/';
        $this->langText         = 'sys_engineering';

        $this->hrefMainDetail   = 'etraningmember/';
        $this->hrefMainNew      = 'new_etraningmember';
        $this->routerPost       = 'postETraningmember';
        $this->routerPost2      = 'etraningmemberCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title21');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list21');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new21');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit21');//編輯

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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js = $isValid = '';
        $no  = 0;
        $aproc = [];
        $closeAry = SHCSLib::getCode('CLOSE');
        $passAry  = SHCSLib::getCode('PASS',1);
        $aid      = ($request->aid)? $request->aid : '';
        $bid      = ($request->bid)? $request->bid : '';
        //開課ＩＤ
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid)
        {
            $msg = Lang::get($this->langText.'.engineering_1010');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = 'pid='.$request->pid;
            $supplyAry  = et_traning_m::getSupplySelect($pid);
            $isOverDate = et_traning::isTraningWithInDate($pid);
        }
        if($request->has('clear'))
        {
            $aid = $bid = '';
            Session::forget($this->hrefMain.'.search');
        }
        if($aid)
        {
            Session::put($this->hrefMain.'.search.aid',$aid);
        } else {
            $aid = Session::get($this->hrefMain.'.search.aid','');
        }
        if($bid)
        {
            Session::put($this->hrefMain.'.search.bid',$bid);
        } else {
            $bid = Session::get($this->hrefMain.'.search.bid','');
        }
        if($bid == 'Y')
        {
            $aproc = ['O'];
            $isValid = 'Y';
        }
        if($bid == 'N')
        {
            $aproc = ['A','P','R','C','B'];
        }
        if($bid == 'O')
        {
            $aproc = ['O'];
            $isValid = 'N';
        }

        //view元件參數
        $CourseName = et_traning::getCourseName($pid);
        $TranDate = et_traning::getTranDate($pid);
        $TranTime = et_traning::getTranTime($pid);
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $CourseInfo = $CourseName ? $Icon . '<b>' . $CourseName . ' ' . $TranDate . ' ' . $TranTime . '</b>' : '';
        $tbTitle  = $this->pageTitleList . $CourseInfo;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        $hrefPDF  = SHCSLib::url($this->hrefPDF,$pid);
        $btnPDF   = $this->pagePrintBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiTraningMemberList($pid,0,$aid,0,$aproc,$isValid);
        Session::put($this->hrefMain.'.traning_id',$pid);
        Session::put($this->hrefMain.'.Record',$listAry);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y' && $isOverDate)$form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefPDF, $btnPDF,3,'','','','_blank'); //列印
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        if($pid)
        {
            //
            $html = $form->select('aid',$supplyAry,$aid,2,Lang::get($this->langText.'.engineering_93'));
            $html.= $form->select('bid',$passAry,$bid,2,Lang::get($this->langText.'.engineering_12'));
            $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
            $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
            $html.= $form->hidden('pid',$request->pid);
            $form->addRowCnt($html);
        }

        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get('sys_supply.supply_12')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_19')]; //成員
        $heads[] = ['title'=>Lang::get('sys_supply.supply_21')]; //身分證
        $heads[] = ['title'=>Lang::get('sys_supply.supply_52')]; //進度
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_80')]; //報名申請
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_81')]; //審查人
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_82')]; //審查時間
        $heads[] = ['title'=>Lang::get($this->langText.'.engineering_73')]; //有效日期

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name8        = $value->supply; //
                $name1        = $value->name; //
                $name2        = SHCSLib::genBCID($value->bc_id); //
                $name3        = $value->isOver == 'Y' ? HtmlLib::Color($value->aproc_name,'red') : $value->aproc_name; //
                $name4        = substr($value->apply_date,0,16); //
                $name5        = $value->pass_user; //
                $name6        = $value->pass_date; //
                $name7        = $value->valid_date; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,$param),Lang::get('sys_btn.btn_30'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '11'=>[ 'name'=> $name8],
                            '1'=>[ 'name'=> $name1],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name7],
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
            //dd($pid,$course);
        }
        //view元件參數
        $pdf_row_max_amt = 10;
        $tbTitle = Lang::get($this->langText.'.pdf18',['head'=>$course]); //header
        $tbDate  = Lang::get($this->langText.'.engineering_92'); //簽到日期

        //-------------------------------------------//
        //  資料內容
        //-------------------------------------------//
        $listAry = $this->getApiTraningMemberList($pid,0,0,0,['P',]);
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
        } else {
            $tmp = [];
            $tmp['supply']= '';
            $tmp['name']  = Lang::get($this->langText.'.engineering_1036');
            $tmp['bcid']  = '';

            $tbAry[$totalPage][$pageRow] = $tmp;
        }
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $pid      = SHCSLib::decode($request->pid);
        //開課
        if(!$pid || !is_numeric($pid))
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param = '?pid='.$request->pid;
        }
        $id = SHCSLib::decode($urlid);
        //view元件參數
        $hrefBack       = $this->hrefMain.$param;
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
            $A1         = $getData->course; //
            $A2         = $getData->supply; //
            $A3         = $getData->name; //
            $A4         = $getData->apply_date; //
            $A5         = $getData->aproc_name; //
            $A11        = $getData->pass_user; //
            $A12        = $getData->pass_date; //
            $A13        = $getData->charge_user; //
            $A14        = $getData->charge_stamp; //


            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //課程
        $html = $A1;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_42'));
        //公司
        $html = $A2;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_88'));
        //學員
        $html = $A3;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_89'));
        //進度
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_12'));
        //報名時間
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_80'));
        //報名處理人員
        $html = $A13.'('.$A14.')';
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_83'));
        //報名處理人員
        $html = $A11.'('.$A12.')';
        $form->add('nameT1', $html,Lang::get($this->langText.'.engineering_81'));

        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        //$submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv  = $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('pid',$request->pid);
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
        if( !$request->has('agreeY') || !$request->id || !$request->pid || !$request->b_supply_id || !$request->member )
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
            $traning_id = SHCSLib::decode($request->pid);
            $id         = SHCSLib::decode($request->id);
            $ip         = $request->ip();
            $menu       = $this->pageTitleMain;
        }
        if(!$traning_id)
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        }
        $isNew = 1;
        $action = ($isNew)? 1 : 2;
        list($et_course_id) = et_traning::getCourseInfo($traning_id);

        $upAry['et_course_id']        = $et_course_id;
        $upAry['et_traning_id']       = $traning_id;
        $upAry['b_supply_id']         = $request->b_supply_id;
        $upAry['member']              = $request->member;
        $upAry['aproc']               = 'R';
//        dd($upAry);
        //新增
        if($isNew)
        {
            $ret = $this->createTraningMemberGroup($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = 0;//$this->setTraning($id,$upAry,$this->b_cust_id);
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
                return \Redirect::to($this->hrefMain.'?pid='.$request->pid);
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
        $pid    = Session::get($this->hrefMain.'.traning_id',0);
        $urlid  = SHCSLib::encode($pid);
        if(!$pid)
        {
            $msg = Lang::get($this->langText.'.engineering_1018');
            return \Redirect::back()->withErrors($msg);
        }
        //課程
        $cource_id = et_traning::getCourseID($pid);
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
            $mebmer1    = view_door_supply_member::getSelect($sid,2,'','',0);
            $supplyMemberAry = array_keys($mebmer1);
            //已經報名成員[]
            $mebmer2    = $this->getTranMemberApplyOrder($cource_id,$sid);
            foreach ($mebmer2 as $uid)
            {
                if(isset($mebmer1[$uid]))
                {
                    unset($mebmer1[$uid]);
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
                    $name1        = $form->checkbox('member[]',$id,'','select_box'); //
                    $name2        = $value; //

                    $tBody[] = ['0'=>[ 'name'=> $name1],
                                '1'=>[ 'name'=> $name2],
                    ];
                }
                $table->addBody($tBody);
            }
            //輸出
            $checkAllBtn = HtmlLib::btn('#',Lang::get('sys_btn.btn_77'),2,'checkAllBtn','','checkAll()');
            $form->add('nameT1', $checkAllBtn.$table->output(),Lang::get($this->langText.'.engineering_85'));
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
        });
        
        var clicked = false;
        function checkAll()
        {
            $(".select_box").prop("checked", !clicked);
            clicked = !clicked;
            btn = clicked ? "'.Lang::get('sys_btn.btn_78').'" : "'.Lang::get('sys_btn.btn_77').'";
            $("#checkAllBtn").html(btn);
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
