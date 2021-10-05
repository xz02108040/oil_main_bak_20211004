<?php

namespace App\Http\Controllers\Emp;

use App\Http\Controllers\Controller;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\SessTraits;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_dept_a;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class EmpController extends Controller
{
    use BcustTrait,BcustATrait,EmpTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | EmpController
    |--------------------------------------------------------------------------
    |
    | 組織職員
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
        $this->hrefMain         = 'emp';
        $this->hrefExcel        = 'exceltoemp';
        $this->langText         = 'sys_emp';

        $this->hrefMainDetail   = 'emp/';
        $this->hrefMainDetail2  = 'user/';
        $this->hrefMainDetail3  = 'person/';
        $this->hrefMainNew      = 'new_emp';
        $this->routerPost       = 'postEmp';

        $this->pageTitleMain    = Lang::get($this->langText.'.title4');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list4');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new4');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit4');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageExcelBtn     = Lang::get('sys_btn.btn_17');//[按鈕]匯入

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
        $listAry  = [];
        $closeAry1 = SHCSLib::getCode('CLOSE');
        $closeAry2 = SHCSLib::getCode('YES');
        $loginAry  = SHCSLib::getCode('LOGIN');
        $storeAry = b_factory::getSelect();
        $aid      = $request->aid;
        $bid      = $request->bid;
        $cid      = $request->cid;
        if($request->has('clear'))
        {
            $aid = $bid = $cid = 0;
            Session::forget($this->hrefMain);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid','');
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$bid)
        {
            $bid = Session::get($this->hrefMain.'.search.bid',0);
        } else {
            Session::put($this->hrefMain.'.search.bid',$bid);
        }
        if(!$cid)
        {
            $cid = Session::get($this->hrefMain.'.search.cid','');
        } else {
            Session::put($this->hrefMain.'.search.cid',$cid);
        }

        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefExcel= $this->hrefExcel;
        $btnExcel = $this->pageExcelBtn;
//        $hrefBack = $this->hrefHome;
//        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiEmpList([0,$bid,0,$aid,$cid]);
        Session::put($this->hrefMain.'.Record',$listAry);
        $deptAry  = be_dept::getSelect(0,0,0,'Y');
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($this->isWirte == 'Y') $form->addLinkBtn($hrefExcel, $btnExcel,1); //新增
        //$form->linkbtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //搜尋
//        $html = $form->select('aid',$storeAry,$aid,2,Lang::get($this->langText.'.emp_7'));
        $html = $form->select('bid',$deptAry,$bid,2,Lang::get($this->langText.'.emp_17'));
        $html.= $form->text('aid',$aid,2,Lang::get($this->langText.'.emp_1'));
        $html.= $form->text('cid',$cid,2,Lang::get($this->langText.'.emp_25'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_25')];
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_1')]; //姓名
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_7')]; //廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_2')]; //職稱
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_3')]; //部門
        //$heads[] = ['title'=>Lang::get($this->langText.'.emp_4')]; //職等
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_10')]; //監造身分
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_26')]; //登入權限
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_24')]; //帳號狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_13')]; //帳號
        $heads[] = ['title'=>Lang::get($this->langText.'.emp_14')]; //帳號

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $id           = $value->b_cust_id;
                $name1        = $value->name; //
                $name2        = $value->title; //
                $name3        = $value->dept; //
                $name4        = $value->emp_no; //
                $name5        = isset($storeAry[$value->b_factory_id])? $storeAry[$value->b_factory_id] : ''; //
                $isClose      = isset($closeAry1[$value->isClose])? $closeAry1[$value->isClose] : '' ; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色
                $isSE         = isset($closeAry2[$value->isSE])? $closeAry2[$value->isSE] : '' ; //停用
                $isSEColor    = $value->isSE == 'Y' ? 5 : 2 ; //停用顏色
                $isLogin      = isset($loginAry[$value->isLogin])? $loginAry[$value->isLogin] : '' ; //停用
                $isLoginColor = $value->isLogin == 'Y' ? 2 : 5 ; //停用顏色

                //按鈕
                $AccountBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,$id,'isEmp=Y'),Lang::get('sys_btn.btn_32'),4); //按鈕
                //按鈕
                $PersonBtn   = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail3,$id,'isEmp=Y'),Lang::get('sys_btn.btn_33'),5); //按鈕
                //
                $btn          = ($this->isWirte == 'Y')? HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $name4],
                            '1'=>[ 'name'=> $name1],
                            '10'=>[ 'name'=> $name5],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '22'=>[ 'name'=> $isSE,'label'=>$isSEColor],
                            '23'=>[ 'name'=> $isLogin,'label'=>$isLoginColor],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
                            '90'=>[ 'name'=> $AccountBtn ],
                            '91'=>[ 'name'=> $PersonBtn ],
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        $storeAry  = b_factory::getSelect();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id);
        //如果沒有資料
        if(!isset($getData->name))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } elseif($this->isWirte != 'Y') {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_write'));
        } else {
            //資料明細
            $A1         = $getData->name; //
            $A2         = $getData->be_dept_id; //
            $A3         = $getData->be_title_id; //
            $A4         = $getData->boss_id; //
            $A5         = $getData->attorney_id; //
            $A6         = $getData->b_factory_id; //
            $A7         = $getData->emp_no; //
            $A8         = ($getData->isSE == 'Y')? true : false;

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isVacate == 'Y')? true : false;
            $A97        = ($getData->isVacate == 'Y')? Lang::get('sys_base.base_10614',['name'=>$getData->vacate_user,'time'=>$getData->vacate_stamp]) : '';

            $titleAry   = be_title::getSelect();
            $empAry     = b_cust_e::getSelect(0,$getData->be_dept_id,0,$id);
            $deptAry    = be_dept::getSelect(0,$A6,0,'Y',0,2);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //姓名
        $html = $form->text('name', $A1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_1'));
        //職員編號
        $html = $form->text('emp_no', $A7);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_15'));
        //廠區
        $html = $form->select('b_factory_id', $storeAry, $A6);
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_7'),1);
        //部門
        $html = $form->select('be_dept_id', $deptAry, $A2);
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_3'),1);
        //職稱
        $html = $form->select('be_title_id', $titleAry, $A3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.emp_2'),1);
        //主管
        $html = $form->select('boss_id', $empAry, $A4);
        $form->add('nameT4', $html,Lang::get($this->langText.'.emp_11'));
        //代理人
//        $html = $form->select('attorney_id', $empAry, $A5);
//        $form->add('nameT5', $html,Lang::get($this->langText.'.emp_12'));
        //監造身分
        $html = $form->checkbox('isSE','Y',$A8);
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_10'));
        if($A99)
        {
            $html = $A97;
            $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_19'));
        }
        //最後異動人員 ＋ 時間
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

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
        //  View -> JavaScript
        //-------------------------------------------//
        $js = '
        $(document).ready(function() {
            $( "#b_factory_id" ).change(function() {
                        var sid = $("#b_factory_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_dept_id option").remove();
                             $.each(result, function(key, val) {
                                $("#be_dept_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#be_dept_id" ).change(function() {
                        var eid = $("#be_dept_id").val();
                        var uid = $("#id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 1, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_title_id option").remove();
                             $("#be_title_id").append($("<option value=\'\' selected></option>"));
                             $.each(result, function(key, val) {
                                $("#be_title_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 2, eid : eid, uid : uid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#boss_id option").remove();
                             $("#attorney_id option").remove();
                             $("#boss_id").append($("<option value=\'\' selected></option>"));
                             $("#attorney_id").append($("<option value=\'\' selected></option>"));
                             $.each(result, function(key, val) {
                                $("#boss_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#attorney_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
                    });
            });
        ';
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
        if( !$request->has('agreeY') || !$request->id || !$request->b_factory_id || !$request->be_dept_id || !$request->be_title_id)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->emp_no && b_cust_e::isEmpNoExist($request->emp_no,SHCSLib::decode($request->id)))
        {
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.emp_1000'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $def_title = sys_param::getParam('DEPT_TITLE_DEFAULT');
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;
        if($isNew)
        {
            if(!$request->name )
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10103'))
                    ->withInput();
            }
        }

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['name']              = $request->name;
        $upAry['emp_no']            = $request->emp_no;
        $upAry['b_factory_id']      = $request->b_factory_id;
        $upAry['be_dept_id']        = $request->be_dept_id;
        $upAry['be_title_id']       = $request->be_title_id ? $request->be_title_id : $def_title;
        $upAry['boss_id']           = $request->boss_id;
        $upAry['attorney_id']       = $request->attorney_id;
        $upAry['isSE']              = ($request->isSE == 'Y')? 'Y' : 'N';
        $upAry['isVacate']          = ($request->isVacate == 'Y')? 'Y' : 'N';
        if($isNew)
        {
            $upAry['bc_type']               = $request->bc_type;
            $upAry['bc_type_app']           = $request->bc_type_app;
            $upAry['account']               = ($request->emp_no)? $request->emp_no : time();
            $upAry['password']              = ($request->emp_no)? $request->emp_no : base64_encode(time());
            $upAry['b_menu_group_id']       = $request->b_menu_group_id;
            //$upAry['isAutoAccount']         = 'Y'; //自動產生帳密
            $upAry['isLogin']               = 'N';
        }
        //dd($upAry);

        //新增
        if($isNew)
        {
            $ret = $this->createBcust($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret1 = $this->setBcust($id,['name'=>$request->name],$this->b_cust_id);
            $ret2 = $this->setEmp($id,$upAry,$this->b_cust_id);
            $ret  = ($ret1 < 0 && $ret2 < 0)? $ret1 : 1;
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
                if($isNew)
                {
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                    LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_e',$id);
                } else {
                    if($ret1)
                    {
                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust',$id);
                    }
                    if($ret2)
                    {
                        LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_cust_e',$id);
                    }
                }

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
     * 單筆資料 新增[職員]
     */
    public function create()
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

        $deptAry        = be_dept::getSelect(0,1,0,'Y',0,2);
        $titleAry       = $empAry = [];
        $storeAry       = b_factory::getSelect();
        //view元件參數
        $hrefBack   = $this->hrefMain;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header

        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //名稱
        $html = $form->text('name');
        $form->add('titleT1', $html,Lang::get('sys_base.base_10707'),1);
        //職員編號
        $html = $form->text('emp_no').HtmlLib::Color(Lang::get($this->langText.'.emp_23'),'red',1);
        $form->add('titleT1', $html,Lang::get($this->langText.'.emp_15'),1);
        //====== 職員身分 ======
        //部門
        $html = $form->select('b_factory_id', $storeAry,1);
        $form->add('nameT1', $html,Lang::get($this->langText.'.emp_7'),1);
        //部門
        $html = $form->select('be_dept_id', $deptAry);
        $form->add('nameT2', $html,Lang::get($this->langText.'.emp_3'),1);
        //職稱
        $html = $form->select('be_title_id', $titleAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.emp_2'),1);
        //主管
        $html = $form->select('boss_id', $empAry);
        $form->add('nameT4', $html,Lang::get($this->langText.'.emp_11'));
        //監造身分
        $html = $form->checkbox('isSE','Y');
        $form->add('isCloseT',$html,Lang::get($this->langText.'.emp_10'));
        //代理人
//        $html = $form->select('attorney_id', $empAry);
//        $form->add('nameT5', $html,Lang::get($this->langText.'.emp_12'));

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv.= $form->hidden('isLogin','N');
        $submitDiv.= $form->hidden('b_menu_group_id',1);
        $submitDiv.= $form->hidden('bc_type',2);
        $submitDiv.= $form->hidden('bc_type_app',0);
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
        $js = '
        $(document).ready(function() {
            $( "#b_factory_id" ).change(function() {
                        var sid = $("#b_factory_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_dept_id option").remove();
                             $.each(result, function(key, val) {
                                $("#be_dept_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
            $( "#be_dept_id" ).change(function() {
                        var eid = $("#be_dept_id").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 1, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_title_id option").remove();
                             $.each(result, function(key, val) {
                                $("#be_title_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 2, eid : eid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#boss_id option").remove();
                             $("#attorney_id option").remove();
                             $.each(result, function(key, val) {
                                $("#boss_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                $("#attorney_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
             });
        });
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
        $ret  = [];
        $data = Session::get($this->hrefMain.'.Record');
        //dd($data);
        if( $data && count($data))
        {
            if($uid)
            {
                foreach ($data as $v)
                {
                    if($v->b_cust_id == $uid)
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
