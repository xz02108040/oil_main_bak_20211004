<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\Engineering\EngineeringTypeTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorklineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_license_type;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_factory_e;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_permit_workitem;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitRPWorkOrderController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitWorklineTrait,WorkPermitWorkOrderlineTrait;
    use WorkPermitWorkOrderProcessTrait ;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitRPWorkOrderController
    |--------------------------------------------------------------------------
    |
    | ??????????????? ????????????????????? ??????
    |
    */

    /**
     * ????????????
     */
    protected $redirectTo = '/';

    /**
     * ?????????
     */
    public function __construct()
    {
        //????????????
        $this->middleware('auth');
        //??????
        $this->hrefHome         = 'wpworkorder';
        $this->hrefMain         = 'exa_wpworkorder';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'exa_wpworkorder/';
        $this->hrefMainNew      = 'new_wpworkorder/';
        $this->routerPost       = 'postExaWpWorkOrder';

        $this->pageTitleMain    = Lang::get($this->langText.'.title21');//?????????
        $this->pageTitleList    = Lang::get($this->langText.'.list21');//????????????
        $this->pageNewTitle     = Lang::get($this->langText.'.new21');//??????
        $this->pageEditTitle    = Lang::get($this->langText.'.edit21');//??????

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[??????]??????
        $this->pageEditBtn      = Lang::get('sys_btn.btn_21');//[??????]??????
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[??????]??????
        $this->pageNextBtn      = Lang::get('sys_btn.btn_37');//[??????]?????????

    }
    /**
     * ????????????
     *
     * @return void
     */
    public function index(Request $request)
    {
        //?????? Session ??????
        $this->getBcustParam();
        $this->getMenuParam();
        //??????
        $out = $js ='';
        $no  = 0;
        $pid        = SHCSLib::decode($request->pid);
        $project    = e_project::getName($pid);
        //view????????????
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.($project ? $Icon.$project : '');//????????????
        $hrefMain = $this->hrefMain;
        $hrefNew  = $this->hrefMainNew;
        $btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // ????????????
        //-------------------------------------------//
        //????????????
        if(!$pid)
        {
            //??????
            $listAry = $this->getApiWorkPermitWorkOrderByProject($this->be_dept_id);
        } else {
            $aproc      = ['A'];
            $wpSearch   = [0,$pid,'','',0];
            $storeSearch= [0,0,0];
            $depSearch  = [0,0,$this->be_dept_id,0,0,0];
            $dateSearch = ['','','Y'];
            $listAry = $this->getApiWorkPermitWorkOrderList(0,$aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch);
            if($request->showtest) dd($pid,$depSearch);
            Session::put($this->hrefMain.'.Record',$listAry);
            Session::forget($this->hrefMain.'.identityMemberAry');
            Session::forget($this->hrefMain.'.old_identityMemberAry');
            Session::forget($this->hrefMain.'.itemworkAry');
            Session::forget($this->hrefMain.'.old_itemworkAry');
            Session::forget($this->hrefMain.'.checkAry');
            Session::forget($this->hrefMain.'.old_checkAry');
        }
        //dd($listAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
//        $form->addLinkBtn($hrefNew, $btnNew,2); //??????
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //??????
            $form->addHr();
        }
        //??????
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //??????
        if(!$pid)
        {
            $heads[] = ['title'=>'NO'];
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_110')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')]; //??????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_116')]; //??????
        } else {
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_115')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_113')]; //??????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_106')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_52')]; //??????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_3')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_125')]; //???????????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_126')]; //??????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_109')]; //??????
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            $aprocColorAry = SHCSLib::getPermitAprocColor();
            foreach($listAry as $value)
            {
                $no++;

                if(!$pid)
                {
                    $id           = $value->e_project_id;
                    $name1        = $value->project_no; //
                    $name2        = $value->project; //
                    $name3        = $value->supply; //
                    $name4        = $value->amt; //
                    $name5        = be_dept::getName($value->be_dept_id2); //

                    //??????
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //??????

                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '5'=>[ 'name'=> $name5],
                        '3'=>[ 'name'=> $name3],
                        '4'=>[ 'name'=> $name4],
                        '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->permit_no; //
                    $name2        = $value->sdate; //
                    $name3        = $value->store; //
                    $name4        = $value->be_dept_id2_name; //
                    $name5        = $value->local.'<br/>'.$value->device; //
                    $name7        = $value->shift_name; //
                    $name10       = $value->wp_permit_danger; //
                    $name6        = $value->supply_worker_name; //
                    $name9        = $value->supply_safer_name; //
                    $name8        = $value->aproc_name; //
                    //$isColor      = $value->aproc == 'A' ? 5 : 2 ; //????????????
                    $isColor      = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 2 ; //??????

                    //??????
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id,'pid='.$request->pid),Lang::get('sys_btn.btn_21'),1); //??????

                    $tBody[] = [
                        '1'=>[ 'name'=> $name1],
                        '2'=>[ 'name'=> $name2],
                        '3'=>[ 'name'=> $name3],
                        '10'=>[ 'name'=> $name5],
                        '11'=>[ 'name'=> $name7],
                        '12'=>[ 'name'=> $name10],
                        '4'=>[ 'name'=> $name4],
                        '6'=>[ 'name'=> $name6],
                        '9'=>[ 'name'=> $name9],
                        '8'=>[ 'name'=> $name8,'label'=>$isColor],
                        '99'=>[ 'name'=> $btn ]
                    ];
                }


            }
            $table->addBody($tBody);
        }
        //??????
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
        //  ??????
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * ???????????? ??????
     */
    public function show(Request $request,$urlid)
    {
        //?????? Session ??????
        $this->getBcustParam();
        $this->getMenuParam();
        $this->forget();
        //??????
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        Session::forget($this->hrefMain.'.identityMemberAry');
        Session::forget($this->hrefMain.'.old_identityMemberAry');
        Session::forget($this->hrefMain.'.itemworkAry');
        Session::forget($this->hrefMain.'.old_itemworkAry');
        Session::forget($this->hrefMain.'.checkAry');
        Session::forget($this->hrefMain.'.old_checkAry');
        //view????????????
        $hrefBack       = $this->hrefMain.'?pid='.$request->pid;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //????????????
        $getData        = $this->getData($id);
        //??????????????????
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //????????????
            $A1         = $getData->wp_permit_id; //
            $A2         = $getData->store; //
            $A3         = $getData->b_factory_id; //
            $A4         = $getData->sdate; //
            $A5         = $getData->b_factory_b_id; //
            $A6         = $getData->be_dept_id2_name; //
            $A7         = $getData->b_factory_memo; ///
            $A8         = $getData->b_factory_a_id; ///
            $A9         = $getData->wp_permit_danger; //
            $A11        = $getData->local; //
            $A13        = $getData->wp_permit_workitem_memo; //
            $A14        = $getData->permit_no; //
            $A15        = $getData->project.' ??? '.$getData->project_no; //
            $A16        = $getData->supply; //
            $A17        = $getData->supply_worker_name;
            $A18        = $getData->supply_safer_name;
            $A19        = $getData->e_project_id; ///
            $A20        = $getData->be_dept_id2; ///
            $A28        = $getData->list_aproc_val; ///
            $A29        = $getData->apply_user_name; ///
            $A30        = $getData->apply_stamp; ///
            $A31        = Lang::get($this->langText.'.permit_155',['name1'=>$A29,'name2'=>$A30]); ///
            $A33        = $getData->b_factory_d_id;
            $A34        = $getData->wp_permit_shift_id;
            $A35        = $getData->b_car_memo;

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A97        = ($getData->isOvertime == 'Y')? true : false;
            $A96        = ($getData->isHoliday == 'Y')? true : false;

            //???????????????
            $lineAry = Session::get($this->hrefMain.'.lineAry',[]);
            if(!count($lineAry))
            {
                $idAry  = $this->getApiWorkPermitWorkLineList($id);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->wp_check_kind_id > 0)
                        {
                            $lineAry[$val->id] = $val->name;
                        }
                    }
                }
                Session::put($this->hrefMain.'.lineAry',$lineAry);
                Session::put($this->hrefMain.'.old_lineAry',$lineAry);
            }
            //?????????
            $checkAry = Session::get($this->hrefMain.'.checkAry',[]);
            if(!count($checkAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderCheckList($id);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->wp_check_kind_id > 0)
                        {
                            $checkAry[$val->wp_check_kind_id] = $val->name;
                        }
                    }
                }
                Session::put($this->hrefMain.'.checkAry',$checkAry);
                Session::put($this->hrefMain.'.old_checkAry',$checkAry);
            }
            //??????????????????
            $itemworkAry = Session::get($this->hrefMain.'.itemworkAry',[]);
            if(!count($itemworkAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderItemList($id);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->wp_permit_workitem_id > 0)
                        {
                            $itemworkAry[$val->wp_permit_workitem_id] = $val->name;
                        }
                    }
                }
                Session::put($this->hrefMain.'.itemworkAry',$itemworkAry);
                Session::put($this->hrefMain.'.old_itemworkAry',$itemworkAry);
            }
            //????????????
            $identityMemberAry  = Session::get($this->hrefMain.'.identityMemberAry',[]);
            $identityMemberAry2 = [];
            if(!count($identityMemberAry))
            {
                $idAry  = $this->getApiWorkPermitWorkerList($id,[0]);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->user_id > 0)
                        {
                            $head = in_array($val->engineering_identity_id,[1,2])? 'A' : str_pad($val->engineering_identity_id, 3, '0', STR_PAD_LEFT);
                            $identityMemberAry[$head.$val->user_id] = $val->engineering_identity_id;
                            $tmp = [];
                            $tmp['door_stime'] = $val->door_stime;
                            $tmp['door_etime'] = $val->door_etime;
                            $tmp['work_time']  = $val->work_stime .'~'.$val->work_etime;
                            $identityMemberAry2[$val->user_id] = $tmp;
                        }
                    }
                }
                Session::put($this->hrefMain.'.work_id',$id);
                Session::put($this->hrefMain.'.store_id',$A3);
                Session::put($this->hrefMain.'.list_aproc',$A28);
                Session::put($this->hrefMain.'.identityMemberAry',$identityMemberAry);
                Session::put($this->hrefMain.'.identityMemberAry2',$identityMemberAry2);
                Session::put($this->hrefMain.'.old_identityMemberAry',$identityMemberAry);
            }
            //??????????????????
            $isEdit = 0;

            $dangerAry  = SHCSLib::getCode('PERMIT_DANGER');
            $deptAry    = b_factory_e::getSelect(0,$A8);
            $localAry   = b_factory_b::getSelect(0,$A8);
            $storeAry   = b_factory::getSelect();
            $doorAry    = b_factory_d::getSelect($A3);
            $shiftAry   = wp_permit_shift::getSelect();
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //????????????
        $html = $A14;//$form->text('permit_no',$A14);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_112'));
        //????????????
        $html = $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_110'),1);
        //??????
        $html = $form->select('wp_permit_shift_id',$shiftAry,$A34);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_52'),1);
        //??????-??????
        $html = $form->select('be_dept_id1',$deptAry);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_102'),1);
        //????????????
        $html = $A6;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_103'),1);
        //????????????
        $html  = $form->select('store1',$storeAry,'',3);
        $html .= $form->select('be_dept_id3',[''=>Lang::get($this->langText.'.permit_10027')],'',3);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_132'));
        //??????
        $html = $A16.HtmlLib::Color($A31,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_101'),1);
        //???????????????
        $html = $A17;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_125'),1);
        //????????????
        $html = $A18;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_126'),1);
        //????????????
        $html = $form->date('sdate',$A4);
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_115'),1);
        //??????
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_113'),1);
        //??????-??????
        $html = $A11;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_158'),1);
        //??????-????????????
        $html = $form->select('b_factory_b_id',$localAry,$A5);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_106'),1);
        //??????-??????
        $html = $form->select('b_factory_d_id',$doorAry,$A33);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_163'),1);
        //????????????
        $html = $A35;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_166'));
        //??????????????????
        $html = $form->textarea('local_memo','','',$A7);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_114'));
        //??????????????????
        $html = $form->textarea('workitem_memo','','',$A13);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_107'));
        //????????????
        $html = '<div id="identityMemberDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_127'),1);

        //????????????
        $form->addHr();
        //????????????
        $html = $form->checkbox('isHoliday','Y',$A96,'','isHoliday','chkHoliday()');
        $html.= HtmlLib::Color(Lang::get($this->langText.'.permit_11010'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_164'));
        //??????????????????
        $html = $form->checkbox('isOvertime','Y',$A97,'');
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_128'));
        //????????????
        $html = $form->select('wp_permit_danger',$dangerAry,$A9);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_3'),1);
        //????????????
        $html = $this->genWorkItem1Html();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_108'),1);
        //?????????
        $html = $this->genWorkItem2Html();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_118'));
        //????????????
        $html = $this->genWorkItem3Html();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_45'),1);
        //w
        $html = $this->genWorkItem4Html();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_48'),1);

        //????????????
        $html  = $form->select('store2',$storeAry,'',3);
        $html .= $form->select('be_dept_id4',[''=>Lang::get($this->langText.'.permit_10027')],'',3);
        $html.= HtmlLib::Color(Lang::get($this->langText.'.permit_11011'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_131'));

        //?????????????????? ??? ??????
        $form->addHr();
        //??????
        $html = $form->textarea('charge_memo','',Lang::get($this->langText.'.permit_10021'));
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_130'));
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));
        //Submit
        $submitDiv  = $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        $submitDiv.= $form->hidden('be_dept_id2',$A20);
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
            loadPage();

            $( "#store1" ).change(function() {
                        var sid = $("#store1").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 3, sid : sid},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_dept_id3 option").remove();
                             $.each(result, function(key, val) {
                                $("#be_dept_id3").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
            });
            $( "#store2" ).change(function() {
                        var sid = $("#store2").val();
                        $.ajax({
                          type:"GET",
                          url: "'.url('/findEmp').'",
                          data: { type: 3, sid : sid,  eid : '.$A20.'},
                          cache: false,
                          dataType : "json",
                          success: function(result){
                             $("#be_dept_id4 option").remove();
                             $.each(result, function(key, val) {
                                $("#be_dept_id4").append($("<option value=\'" + key + "\'>" + val + "</option>"));
                             });
                          },
                          error: function(result){
                                alert("ERR");
                          }
                        });
            });
            $("#sdate").datepicker({
                format: "yyyy-mm-dd",
                startDate: "today",
                language: "zh-TW"
            });
            
            
        });
        function chkHoliday()
        {
            if($("#isHoliday").is(":checked"))
            {
                $("#wp_permit_danger").val("A");
            }
        }
        
        function clearCheck(type)
        {
            if(type == 1)
            {
                $(".checkgroup").each(function() {
                    $(this).prop("checked", false);
                });
            }
            if(type == 2)
            {
                $(".dangergroup").each(function() {
                    $(this).prop("checked", false);
                });
            }
            if(type == 3)
            {
                $(".linegroup").each(function() {
                    $(this).prop("checked", false);
                });
            }
        }
        function toEven(itemwork,type)
        {
            if(type == "A")
            {
                calDateDay();
            }
            $.ajax({
                  type:"GET",
                  url: "'.url('/findPermitItem').'",
                  data: { type: 1, wid : itemwork},
                  cache: false,
                  dataType : "json",
                  success: function(result){

                     if(Object.keys(result.danger).length > 0)
                     {
                        $.each(result.danger, function(key, val) {
                            $("#danger"+key).prop("checked",true);
                         });
                     }
                     if(Object.keys(result.check).length > 0)
                     {
                        $.each(result.check, function(key, val) {
                            $("#check"+key).prop("checked",true);
                         });
                     }
                  },
                  error: function(result){
                        //alert("ERR:1");
                  }
            });
        }
        function calDateDay()
        {
            var sdate= $("#sdate").val();
            var date = new Date(sdate);
            var day  = date.getDay();
            //alert(sdate+" => "+day);
            var dayType = (day === 0 || day === 6)? 1 : 0;
            if(dayType)
            {
                $("#wp_permit_danger").val("A");
            }
        }

        function loadPage()
        {
            $.ajax({
                  type:"GET",
                  url: "'.url('/findPermitWorker').'",
                  data: { type: 1, wid : '.$A1.', pid : '.$A19.', isCheck : '.$isEdit.'},
                  cache: false,
                  dataType : "text",
                  success: function(result){
                     $("#identityMemberDiv").html(result);
                  },
                  error: function(result){
                        //alert("ERR:1");
                  }
            });

        }

        ';

        //-------------------------------------------//
        //  ??????
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * ??????/????????????
     * @param Request $request
     * @return mixed
     */
    public function post(Request $request)
    {
        //dd($request->all());

        /**
         * ????????????
         */
        $rules = [
            'be_dept_id1'       => 'required',
            'wp_permit_danger'  => 'required',
            'sdate'             => 'required',
            'b_factory_b_id'    => 'required',
        ];
        $messages = [
            'be_dept_id1.required'      => Lang::get('validation.required',['attribute'=>Lang::get($this->langText.'.permit_102')]),
            'wp_permit_danger.required' => Lang::get('validation.required',['attribute'=>Lang::get($this->langText.'.permit_3')]),
            'sdate.required'            => Lang::get('validation.required',['attribute'=>Lang::get($this->langText.'.permit_115')]),
            'b_factory_b_id.required'   => Lang::get('validation.required',['attribute'=>Lang::get($this->langText.'.permit_106')]),
        ];
        request()->validate($rules,$messages);

        //?????????????????????
        if($request->has('agreeN'))
        {
            if(!$request->charge_memo ){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10021'))
                    ->withInput();
            }
        }
        //??????????????????
        elseif($request->has('agreeY'))
        {
            //?????????????????? ????????????
            $limit_day  = sys_param::getParam('PERMIT_APPLY_MAX_DAY');
            $limit_date = SHCSLib::addDay($limit_day);

            if(!in_array($request->wp_permit_danger,['A','B','C']) ){
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10103'))
                    ->withInput();
            }
            //???????????????????????????
            elseif(!$request->itemwork || !count($request->itemwork)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10017'))
                    ->withInput();
            }
            //???????????????
            elseif(!$request->line || !count($request->line)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10035'))
                    ->withInput();
            }
            //?????????????????????
            elseif(!$request->be_dept_id1){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10031'))
                    ->withInput();
            }
            //A?????????????????????????????????
            elseif($request->wp_permit_danger == 'A' && !$request->be_dept_id3){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10030'))
                    ->withInput();
            }
            //??????????????????
            elseif(strtotime($request->sdate) < strtotime(date('Y-m-d')) ){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10018'))
                    ->withInput();
            }
            //??????????????????<????????????>
            elseif(strtotime($request->sdate) > strtotime($limit_date) ){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10020',['day'=>$limit_day]))
                    ->withInput();
            }
            //????????????
            elseif($request->be_dept_id4 > 0 && $request->be_dept_id4 == $request->be_dept_id2 ){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10023'))
                    ->withInput();
            }
        }
        //dd($request->all());
        $this->getBcustParam();
        $id = SHCSLib::decode($request->id);
        $ip   = $request->ip();
        $menu = $this->pageTitleMain;
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();
        if(!$isNew)
        {
            $upAry['id']            = $id;
        }
        if($request->has('agreeY'))
        {
            $othermemoData = sys_param::getParam('PERMIT_WORKITEM_MEMO_ID',1);
            $othermemoAry  = explode(',',$othermemoData);
            $itemwork      = (isset($request->itemwork) && count($request->itemwork)) ? $request->itemwork : [];
            foreach ($itemwork as $itemwork_id => $itemwork_val)
            {
                if(in_array($itemwork_id,$othermemoAry) && !strlen($itemwork_val['memo']))
                {
                    unset($itemwork[$itemwork_id]);
                }
            }

            $line      = (isset($request->line) && count($request->line)) ? $request->line : [];
            foreach ($line as $line_id => $line_val)
            {
                if(wp_permit_pipeline::isText($line_id) && !strlen($line_val['memo']))
                {
                    unset($line[$line_id]);
                }
            }
            //?????????????????????
            if(!count($itemwork)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10032'))
                    ->withInput();
            }
            //????????? ???????????????
            if(!count($line)){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10035'))
                    ->withInput();
            }
            //???????????????????????????
            if(!strlen(trim($request->local_memo))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10033'))
                    ->withInput();
            }
            //???????????????????????????
            if(!strlen(trim($request->workitem_memo))){
                return \Redirect::back()
                    ->withErrors(Lang::get($this->langText.'.permit_10034'))
                    ->withInput();
            }

            $upAry['itemwork']                  = $itemwork;
            $upAry['check']                     = (isset($request->check) && count($request->check)) ? $request->check : [];
            $upAry['danger']                    = (isset($request->danger) && count($request->danger)) ? $request->danger : [];
            $upAry['line']                      = $line;
            $upAry['b_factory_a_id']            = is_numeric($request->b_factory_a_id) ? $request->b_factory_a_id : '';
            $upAry['b_factory_b_id']            = is_numeric($request->b_factory_b_id) ? $request->b_factory_b_id : '';
            $upAry['b_factory_d_id']            = is_numeric($request->b_factory_d_id) ? $request->b_factory_d_id : '';
            $upAry['wp_permit_shift_id']        = ($request->wp_permit_shift_id) ? $request->wp_permit_shift_id : '';
            $upAry['wp_permit_danger']          = ($request->wp_permit_danger) ? $request->wp_permit_danger : '';
            $upAry['sdate']                     = CheckLib::isDate($request->sdate) ? $request->sdate : '';
            $upAry['edate']                     = CheckLib::isDate($request->sdate) ? $request->sdate : '';
            $upAry['be_dept_id1']               = is_numeric($request->be_dept_id1) ? $request->be_dept_id1 : 0;
            $upAry['be_dept_id3']               = is_numeric($request->be_dept_id3) ? $request->be_dept_id3 : 0;
            $upAry['be_dept_id4']               = is_numeric($request->be_dept_id4) ? $request->be_dept_id4 : 0;
            $upAry['b_factory_memo']            = strlen(trim($request->local_memo)) ? trim($request->local_memo) : '';
            $upAry['wp_permit_workitem_memo']   = strlen(trim($request->workitem_memo)) ? trim($request->workitem_memo) : '';
            $upAry['isHoliday']                 = $request->isHoliday ? 'Y' : 'N';
        }

        $upAry['aproc']                     = ($request->has('agreeY'))? 'W' : 'B'; //???????????? ??????????????????
        $upAry['charge_memo']               = $request->charge_memo;

//        dd($upAry);
        //??????
        if($isNew)
        {
            $ret = 0;
            $id  = $ret;
        } else {
            //??????
            $ret = $this->setWorkPermitWorkOrder($id,$upAry,$this->b_cust_id);
        }
        //2-1. ????????????
        if($ret)
        {
            //????????????????????????
            if($ret === -1)
            {
                $msg = Lang::get('sys_base.base_10109');
                return \Redirect::back()->withErrors($msg);
            } else {
                //????????????
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_list',$id);
                //??????????????????????????????????????????->???????????????
                $isOk = ($upAry['aproc'] == 'W')? 'Y' : 'N';
                $this->pushToRPPermitApplyResult($id,$isOk);

                //2-1-2 ?????? ????????????
                Session::flash('message',Lang::get('sys_base.base_10104'));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.base_10105');
            //2-2 ????????????
            return \Redirect::back()->withErrors($msg);
        }
    }

    /**
     * ?????? ???????????????????????????
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

    protected function forget()
    {
        Session::forget($this->hrefMain.'.identityMemberAry');
        Session::forget($this->hrefMain.'.old_identityMemberAry');
        Session::forget($this->hrefMain.'.itemworkAry');
        Session::forget($this->hrefMain.'.old_itemworkAry');
        Session::forget($this->hrefMain.'.checkAry');
        Session::forget($this->hrefMain.'.old_checkAry');
        Session::forget($this->hrefMain.'.lineAry');
        Session::forget($this->hrefMain.'.old_lineAry');
        Session::forget($this->hrefMain.'.dangerAry');
        Session::forget($this->hrefMain.'.old_dangerAry');
        Session::forget($this->hrefMain.'.work_id');
        Session::forget($this->hrefMain.'.store_id');
    }

    protected function genWorkItem1Html($SelectedAry=[])
    {
        //?????????????????????
        $showRowMax     = 5;

        //3. ????????????
        //?????????????????????
        $table = new TableLib();
        //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_9')];  //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_10')]; //????????????
        $table->addHead($heads,0);
        for($i = 1; $i<=3 ; $i++)
        {
            $itemHtml       = '';
            $showRowCnt     = 1;
            $selectAllAry   = wp_permit_workitem::getSelect($i,0,0);
            $itemTitle      = wp_permit_kind::getName($i);

            foreach ($selectAllAry as $id => $name)
            {
                if($showRowCnt == 1) $itemHtml .= '<div >';
                $checked = (isset($SelectedAry[$id]))? true : false;
                $className = ($i == 2)? 'itemworkA' : 'itemworkB';
                $classFun  = ($i == 2)? 'A' : 'B';

                if(wp_permit_workitem::isText($id))
                {
                    $itemHtml .= '<label>'.$name.'</label>&nbsp;&nbsp;';
                    $itemHtml .= \Form::text('itemwork['.$id.'][memo]','');
                } else {
                     $itemHtml .= FormLib::checkbox('itemwork['.$id.'][val]',$id,$checked,$className,'itemwork'.$id,'toEven(this.value,"'.$classFun.'")');
                     $itemHtml .= '<label>'.$name.'</label>&nbsp;&nbsp;';
                }
                $showRowCnt++;
                if($showRowCnt == $showRowMax)
                {
                    $itemHtml .= '</div>';
                    $showRowCnt = 1;
                }
            }

            $tBody[] = ['0' =>[ 'name'=> $itemTitle,'b'=>1,'style'=>'width:10%;'],
                '12'=>[ 'name'=> $itemHtml],
            ];
        }
        $table->addBody($tBody);
        //??????????????????
        return $table->output();
    }

    protected function genWorkItem2Html($SelectedAry=[])
    {
        $html = '<div class="form-group">'.FormLib::addButton('clearCheck1',Lang::get('sys_btn.btn_40'),3,'','','','clearCheck(1)').'</div>';
        //1. ?????????????????????
        $selectAllAry   = wp_check_kind::getSelect(0,0,[1]);

        //3. ????????????
        $showRowMax = 5;
        $showRowCnt = 1;
        foreach ($selectAllAry as $id => $name)
        {
            if($showRowCnt == 1) $html .= '<div class="form-group">';
            $checked = (isset($SelectedAry[$id]))? true : false;

            $html .= FormLib::checkbox('check['.$id.']',$id,$checked,'checkgroup','check'.$id);
            $html .= '<label>'.$name.'</label>';
            $showRowCnt++;
            if($showRowCnt == $showRowMax)
            {
                $html .= '</div>';
                $showRowCnt = 1;
            }
        }

        return $html;
    }

    protected function genWorkItem3Html($SelectedAry=[])
    {
        $default = sys_param::getParam('PERMIT_DANGER_DEFULT',0);
        $html = '<div class="form-group">'.FormLib::addButton('clearCheck1',Lang::get('sys_btn.btn_40'),3,'','','','clearCheck(2)').'</div>';
        //1. ??????????????????
        $dangerAllAry   = wp_permit_danger::getSelect(0);

        //3. ????????????
        $showRowMax = 5;
        $showRowCnt = 1;
        foreach ($dangerAllAry as $danger_id => $danger_name)
        {
            if($showRowCnt == 1) $html .= '<div class="form-group">';
            $checked = (isset($SelectedAry[$danger_id]))? true : false;
            if($danger_id == $default) $checked = true;

            $html .= FormLib::checkbox('danger['.$danger_id.']',$danger_id,$checked,'dangergroup','danger'.$danger_id);
            $html .= '<label>'.$danger_name.'</label>';
            $showRowCnt++;
            if($showRowCnt == $showRowMax)
            {
                $html .= '</div>';
                $showRowCnt = 1;
            }
        }

        return $html;
    }


    protected function genWorkItem4Html($SelectedAry=[])
    {
        $html = '<div class="form-group">'.FormLib::addButton('clearCheck3',Lang::get('sys_btn.btn_40'),3,'','','','clearCheck(3)').'</div>';
        //1. ??????????????????
        $selectAllAry   = wp_permit_pipeline::getSelect(0);
//        dd($selectAllAry);
        //3. ????????????
        $showRowMax = 3;
        $showRowCnt = 1;
        foreach ($selectAllAry as $key_id => $name)
        {
            if($showRowCnt == 1) $html .= '<div class="form-group">';
            $checked = (isset($SelectedAry[$key_id]))? true : false;
            if(wp_permit_workitem::isText($key_id))
            {
                $html .= '<label>'.$name.'</label>&nbsp;&nbsp;';
                $html .= \Form::text('line['.$key_id.'][memo]','');
            } else {
                $html .= FormLib::checkbox('line['.$key_id.'][val]',$key_id,$checked,'linegroup','line'.$key_id);
                $html .= '<label>'.$name.'</label>&nbsp;&nbsp;';
            }
            $showRowCnt++;
            if($showRowCnt == $showRowMax)
            {
                $html .= '</div>';
                $showRowCnt = 1;
            }
        }

        return $html;
    }
}
