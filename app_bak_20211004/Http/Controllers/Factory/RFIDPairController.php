<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Http\Traits\Factory\RFIDPairTrait;
use App\Http\Traits\Factory\RFIDTrait;
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
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_e;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_invalid_type;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\View\view_log_door_today;
use App\Model\View\view_used_rfid;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class RFIDPairController extends Controller
{
    use RFIDTrait,RFIDPairTrait,SessTraits;
    /*
    |--------------------------------------------------------------------------
    | RFIDPairController
    |--------------------------------------------------------------------------
    |
    | RFID 配對 維護
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
        $this->hrefHome         = 'rfid';
        $this->hrefMain         = 'rfidpair';
        $this->langText         = 'sys_rfid';

        $this->hrefMainDetail   = 'rfidpair/';
        $this->hrefMainNew      = 'new_rfidpair/';
        $this->routerPost       = 'postRFIDpair';

        $this->pageTitleMain    = Lang::get($this->langText.'.title2');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list2');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new2');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit2');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_7');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_13');//[按鈕]編輯
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
        $this->isWirte = SHCSLib::checkUriWrite($this->uri);
        //參數
        $out = $js ='';
        $no  = 0;
        $closeAry = SHCSLib::getCode('CLOSE');
        $pid      = SHCSLib::decode($request->pid);
        if(!$pid && !(is_numeric($pid) && $pid > 0))
        {
            $msg = Lang::get($this->langText.'.rfid_1000');
            return \Redirect::back()->withErrors($msg);
        } else {
            $param      = '?pid='.$request->pid;
            $rfid       = b_rfid::getCode($pid);
            $isClose    = b_rfid::isClose($pid);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$rfid;//列表標題
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew.$request->pid;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefHome;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $listAry = $this->getApiRFIDPairList($pid);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.pid',SHCSLib::decode($request->pid));
        Session::put($this->hrefMain.'.param',$param);

        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        if(!$isClose && $this->isWirte == 'Y') $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        $form->addLinkBtn($hrefBack, $btnBack,1); //返回
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        $heads[] = ['title'=>'NO'];
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_3')];  //所屬廠區
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_10')];  //部門
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_4')];  //所屬場地
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_7')];  //配對成員
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_8')]; //配對承攬商
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_9')];  //配對車輛
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_5')];  //開始日期
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_6')];  //結束日期
        $heads[] = ['title'=>Lang::get($this->langText.'.rfid_13')]; //狀態

        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;
                $id           = $value->id;
                $name1        = $value->store; //
                $name2        = $value->local; //
                $name3        = $value->b_cust; //
                $name4        = $value->supply; //
                $name5        = $value->car; //
                $name6        = $value->sdate; //
                $name7        = $value->edate; //
                $name8        = $value->dept; //
                $isClose      = isset($closeAry[$value->isClose])? $closeAry[$value->isClose] : ''; //停用
                $isCloseColor = $value->isClose == 'Y' ? 5 : 2 ; //停用顏色

                //按鈕
                $btn          = ($this->isWirte == 'Y')?HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1) : ''; //按鈕

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '1'=>[ 'name'=> $name1],
                            '11'=>[ 'name'=> $name8],
                            '2'=>[ 'name'=> $name2],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '6'=>[ 'name'=> $name6],
                            '7'=>[ 'name'=> $name7],
                            '21'=>[ 'name'=> $isClose,'label'=>$isCloseColor],
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
        $id         = SHCSLib::decode($urlid);
        $pid        = Session::get($this->hrefMain.'.pid');
        $param      = Session::get($this->hrefMain.'.param');
        $rfidType   = b_rfid::getType($pid);
        $useid      = b_rfid::getUsedId($pid);
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
            $isUsed     = ($useid == $getData->id)? 1 : 0;
            //資料明細
            $A1         = $getData->store; //
            $A2         = $getData->local; //
            $A3         = $getData->b_cust; //
            $A4         = $getData->supply; //
            $A5         = $getData->car; //
            $A6         = $getData->dept; //

            $A10         = $getData->b_factory_id; //
            $A11         = $getData->b_factory_a_id; //
            $A12         = $getData->b_cust_id; //
            $A13         = $getData->b_supply_id; //
            $A14         = $getData->b_car_id; //
            $A15         = $getData->sdate; //
            $A16         = $getData->edate; //
            $A17         = $getData->be_dept_id; //

            $isIn        = ($A12)? view_log_door_today::isIn($A12) : true ;
            $A97         = ($getData->close_user)? Lang::get('sys_base.base_10614',['name'=>$getData->close_user,'time'=>$getData->close_stamp]) : ''; //
            $A98         = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99         = ($getData->isClose == 'Y')? true : false;

            //該類別 目前已經使用的名單
            $usedAry = view_used_rfid::getSelect($rfidType);
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);

        if($isUsed)
        {
            //廠區
            if(in_array($rfidType,[1,2,3,4]))
            {
                $html  = $A1;
                $html .= $form->hidden('b_factory_id',$A10);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_3'),1);
            }
            //部門
            if(in_array($rfidType,[1,2]))
            {
                $deptAry        = b_factory_e::getSelect($A10);
                $html = $form->select('be_dept_id',$deptAry,$A17);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_10'),1);
            }

            //承攬商
            if(in_array($rfidType,[5,6]))
            {
                $supplyAry      = b_supply::getSelect();
                $html = $form->select('b_supply_id',$supplyAry,$A13);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_8'),1);
            }
            //場地
            if(in_array($rfidType,[3,4]))
            {
                $localkind     = ($rfidType == 4)? 3 : 0;
                $localAry      = b_factory_a::getSelect($A10,$localkind);

                foreach ($usedAry as $val)
                {
                    if(isset($localAry[$val]) && $val != $A11)
                    {
                        unset($localAry[$val]);
                    }
                }


                $html = $form->select('b_factory_a_id',$localAry,$A11);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_4'),1);
            }
            //職員
            if(in_array($rfidType,[1,5]))
            {
                $empAry = ($rfidType == 1)? b_cust_e::getSelect(0,$A17) : b_supply_member::getSelect($A13);

                foreach ($usedAry as $val)
                {
                    if(isset($empAry[$val]) && $val != $A12)
                    {
                        unset($empAry[$val]);
                    }
                }

                $html = $form->select('b_cust_id',$empAry,$A12);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_7'),1);
            }
            //車輛
            if(in_array($rfidType,[2,6]))
            {
                $carAry      = ($rfidType == 2)? b_car::getSelect($A17) : b_car::getSelect(0,$A13);

                foreach ($usedAry as $val)
                {
                    if(isset($carAry[$val]) && $val != $A14)
                    {
                        unset($carAry[$val]);
                    }
                }

                $html = $form->select('b_car_id',$carAry,$A14);
                $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_9'),1);
            }
            //停用MEMO
            $invalidAry = b_rfid_invalid_type::getSelect();
            $html = $form->select('rfid_invalid_type',$invalidAry,1);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.rfid_18'));
            //停用
            $html = (!$isIn)? $form->checkbox('isClose','Y',$A99) : HtmlLib::Color(Lang::get($this->langText.'.rfid_19'),'red',1);
            $form->add('isCloseT',$html,Lang::get($this->langText.'.rfid_14'));

        } else {
            //廠區
            $html = $A1;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_3'));
            //場地
            $html = $A2;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_4'));
            //部門
            $html = $A6;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_10'));
            //成員
            $html = $A3;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_7'));
            //承攬商
            $html = $A4;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_8'));
            //車輛
            $html = $A5;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_9'));
            //開始日期
            $html = $A15;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_5'));
            //結束日期
            $html = $A16;
            $form->add('nameT1', $html,Lang::get($this->langText.'.rfid_6'));
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
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('rfidtype',$rfidType);
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

            $( "#b_factory_id" ).change(function() {
                        var type = $("#rfidtype").val();
                        var sid  = $("#b_factory_id").val();
                        if(type == 1 || type == 2)
                        {
                            $.ajax({
                              type:"GET",
                              url: "'.url('/findEmp').'",
                              data: { type: 3, sid : sid},
                              cache: false,
                              dataType : "json",
                              success: function(result){
                                 $("#be_dept_id option").remove();
                                 $("#b_cust_id option").remove();
                                 $("#b_car_id option").remove();
                                 $.each(result, function(key, val) {
                                    $("#be_dept_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                 });
                              },
                              error: function(result){
                                    alert("ERR");
                              }
                            });
                        }
                        if(type == 3 && type == 4)
                        {
                            $.ajax({
                              type:"GET",
                              url: "'.url('/findLocal').'",
                              data: { type: 1, fid : sid},
                              cache: false,
                              dataType : "json",
                              success: function(result){
                                 $("#b_factory_a_id option").remove();
                                 $.each(result, function(key, val) {
                                    $("#b_factory_a_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                 });
                              },
                              error: function(result){
                                    alert("ERR");
                              }
                            });
                        }
             });
            $( "#be_dept_id" ).change(function() {
                var type = $("#rfidtype").val();
                var sid  = $("#b_factory_id").val();
                var eid  = $("#be_dept_id").val();
                if(type == 1)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findEmp').'",
                      data: { type: 2, sid : sid, eid : eid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_cust_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_cust_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
                if(type == 2)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findEmp').'",
                      data: { type: 4, sid : sid, eid : eid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_car_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_car_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
            });
            $( "#b_supply_id" ).change(function() {
                var type = $("#rfidtype").val();
                var sid  = $("#b_supply_id").val();
                if(type == 5)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findContractor').'",
                      data: { type: 1, sid : sid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_cust_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_cust_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
                if(type == 6)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findContractor').'",
                      data: { type: 2, sid : sid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_car_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_car_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }

            });
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
        if( !$request->has('agreeY') || !$request->id || !$request->rfidtype)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        elseif($request->rfidtype == 1 && (!$request->b_factory_id || !$request->be_dept_id || !$request->b_cust_id))
        {
            //請填寫廠區部門與人員資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1001'))
                ->withInput();
        }
        elseif($request->rfidtype == 2 && (!$request->b_factory_id || !$request->be_dept_id ))
        {
            //請填寫廠區部門與車輛資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1002'))
                ->withInput();
        }
        elseif($request->rfidtype == 3 && (!$request->b_factory_id || !$request->b_factory_a_id))
        {
            //請填寫廠區與場地資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1003'))
                ->withInput();
        }
        elseif($request->rfidtype == 4 && (!$request->b_factory_id || !$request->b_factory_a_id))
        {
            //請填寫廠區與場地資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1003'))
                ->withInput();
        }
        elseif($request->rfidtype == 5 && (!$request->b_supply_id || !$request->b_cust_id))
        {
            //請填寫承攬商與人員資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1004'))
                ->withInput();
        }
        elseif($request->rfidtype == 6 && (!$request->b_supply_id || !$request->b_car_id))
        {
            //請填寫承攬商與車輛資料
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1005'))
                ->withInput();
        }
        elseif($request->isClose == 'Y' && (!$request->rfid_invalid_type))
        {
            //請選擇停用原因
            return \Redirect::back()
                ->withErrors(Lang::get($this->langText.'.rfid_1017'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id   = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
            $pid  = Session::get($this->hrefMain.'.pid');
            $param= Session::get($this->hrefMain.'.param');
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        $upAry['b_rfid_id']         = $pid;
        $upAry['sdate']             = isset($request->sdate) ? $request->sdate : '';
        $upAry['edate']             = isset($request->edate) ? $request->edate : '';
        $upAry['b_factory_id']      = isset($request->b_factory_id) ? $request->b_factory_id : 0;
        $upAry['b_factory_a_id']    = isset($request->b_factory_a_id) ? $request->b_factory_a_id : 0;
        $upAry['be_dept_id']        = isset($request->be_dept_id) ? $request->be_dept_id : 0;
        $upAry['b_cust_id']         = isset($request->b_cust_id) ? $request->b_cust_id : 0;
        $upAry['b_car_id']          = isset($request->b_car_id) ? $request->b_car_id : 0;
        $upAry['b_supply_id']       = isset($request->b_supply_id) ? $request->b_supply_id : 0;
        $upAry['rfid_invalid_type'] = isset($request->rfid_invalid_type) ? $request->rfid_invalid_type : 0;
        $upAry['isClose']           = ($request->isClose == 'Y')? 'Y' : 'N';

        //新增
        if($isNew)
        {
            $ret = $this->createRFIDPair($upAry,$this->b_cust_id);
            $id  = $ret;
        } else {
            //修改
            $ret = $this->setRFIDPair($id,$upAry,$this->b_cust_id);
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'b_rfid_a',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain.$param);
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
        $pid        = Session::get($this->hrefMain.'.pid');
        $param      = Session::get($this->hrefMain.'.param');
        $rfidType   = b_rfid::getType($pid);
        $sid        = b_rfid::getStore($pid);
        $store      = b_factory::getName($sid);
        $deptAry    = b_factory_e::getSelect($sid);

        //view元件參數
        $hrefBack   = $this->hrefMain.$param;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle; //table header


        //-------------------------------------------//
        //  View -> Form
        //  1. 職員卡
        //  2. 職員車輛卡
        //  3. 場地卡
        //  4. 訪客門禁卡
        //  5. 承攬商門禁卡
        //  6. 承攬商車輛門禁卡
        //-------------------------------------------//

        //Form
        $form = new FormLib(1,array($this->routerPost,-1),'POST',1,TRUE);
        //廠區
        if(in_array($rfidType,[1,2,3,4]))
        {
            $storeAry   = b_factory::getSelect();
            $html       = $form->select('b_factory_id',$storeAry,$sid);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_3'),1);
        }
        //部門
        if(in_array($rfidType,[1,2]))
        {
            $html = $form->select('be_dept_id',$deptAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_10'),1);
        }

        //承攬商
        if(in_array($rfidType,[5,6]))
        {
            $supplyAry      = b_supply::getSelect();
            $html = $form->select('b_supply_id',$supplyAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_8'),1);
        }
        //場地
        if(in_array($rfidType,[3,4]))
        {
            $localkind     =  3;
            $localAry      = b_factory_a::getSelect($sid,$localkind);
            $html = $form->select('b_factory_a_id',$localAry);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_4'),1);
        }
        //職員
        if(in_array($rfidType,[1,5]))
        {
            $html = $form->select('b_cust_id',[]);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_7'),1);
            if($rfidType == 5)
            {
                $html = HtmlLib::Color(Lang::get($this->langText.'.rfid_1015'),'red',1);
                $form->add('nameT3', $html,Lang::get('sys_base.base_10018'),1);
            }
        }
        //車輛
        if(in_array($rfidType,[2,6]))
        {
            $html = $form->select('b_car_id',[]);
            $form->add('nameT3', $html,Lang::get($this->langText.'.rfid_9'),1);
        }

        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_7'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv .= $form->hidden('id',SHCSLib::encode(-1));
        $submitDiv .= $form->hidden('rfidtype',$rfidType);
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

            $( "#b_factory_id" ).change(function() {
                        var type = parseInt($("#rfidtype").val(),0);
                        var sid  = $("#b_factory_id").val();
                        if(type == 1 || type == 2)
                        {
                            $.ajax({
                              type:"GET",
                              url: "'.url('/findEmp').'",
                              data: { type: 3, sid : sid},
                              cache: false,
                              dataType : "json",
                              success: function(result){
                                 $("#be_dept_id option").remove();
                                 $("#b_cust_id option").remove();
                                 $("#b_car_id option").remove();
                                 $.each(result, function(key, val) {
                                    $("#be_dept_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                 });
                              },
                              error: function(result){
                                    alert("ERR");
                              }
                            });
                        }
                        if(type == 3 || type == 4)
                        {
                            $.ajax({
                              type:"GET",
                              url: "'.url('/findLocal').'",
                              data: { type: 1, kid: 3, fid : sid},
                              cache: false,
                              dataType : "json",
                              success: function(result){
                                 $("#b_factory_a_id option").remove();
                                 $.each(result, function(key, val) {
                                    $("#b_factory_a_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                                 });
                              },
                              error: function(result){
                                    alert("ERR");
                              }
                            });
                        }
             });
            $( "#be_dept_id" ).change(function() {
                var type = $("#rfidtype").val();
                var sid  = $("#b_factory_id").val();
                var eid  = $("#be_dept_id").val();

                if(type == 1)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findEmp').'",
                      data: { type: 2, sid : sid, eid : eid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_cust_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_cust_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
                if(type == 2)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findEmp').'",
                      data: { type: 4, sid : sid, eid : eid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_car_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_car_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
            });
            $( "#b_supply_id" ).change(function() {
                var type = $("#rfidtype").val();
                var sid  = $("#b_supply_id").val();
                if(type == 5)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findContractor').'",
                      data: { type: 3, sid : sid, rfidtype : type},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_cust_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_cust_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }
                if(type == 6)
                {
                    $.ajax({
                      type:"GET",
                      url: "'.url('/findContractor').'",
                      data: { type: 2, sid : sid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#b_car_id option").remove();
                         $.each(result, function(key, val) {
                            $("#b_car_id").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                         });
                      },
                      error: function(result){
                            alert("ERR");
                      }
                    });
                }

            });
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
