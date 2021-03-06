<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\Engineering\EngineeringTypeTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
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
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_check_kind;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_list;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitWorkOrderAddItemController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitWorkOrderlineTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitWorkOrderController
    |--------------------------------------------------------------------------
    |
    | ?????? ????????????????????? ??????
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
        $this->hrefMain1        = 'wpworkorder_add1';
        $this->hrefMain2        = 'wpworkorder_add2';
        $this->hrefOrder        = 'new_wpworkorder';
        $this->langText         = 'sys_workpermit';

        $this->routerPost1      = 'postWpWorkOrderAdd1';
        $this->routerPost2      = 'postWpWorkOrderAdd2';

        $this->pageTitleMain    = Lang::get($this->langText.'.title20');//?????????
        $this->pageTitleList    = Lang::get($this->langText.'.list20');//????????????
        $this->pageNewTitle1    = Lang::get($this->langText.'.add20_1');//??????
        $this->pageNewTitle2    = Lang::get($this->langText.'.add20_2');//??????
        $this->pageEditTitle    = Lang::get($this->langText.'.edit20');//??????

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[??????]??????
        $this->pageEditBtn      = Lang::get('sys_btn.btn_30');//[??????]??????
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[??????]??????
        $this->pageNextBtn      = Lang::get('sys_btn.btn_37');//[??????]?????????

    }

    /**
     * ??????????????????
     * ???????????? ??????
     */
    public function AddMember(Request $request)
    {
        //?????? Session ??????
        $this->getBcustParam();
        $this->getMenuParam();
        //??????
        $js = $contents = '';
        $addworkerAry   = [];
        $isCheck        = 0;

        $permit_kind                = Session::get('wpworkorder.permit_kind', 0);
        $work_dept_id               = Session::get('wpworkorder.work_dept_id', 0);
        $project_id                 = Session::get('wpworkorder.project_id', 0);
        $edate                      = Session::get('wpworkorder.edate', date('Y-m-d'));
        $pid                        = Session::get('wpworkorder.pid', 0);
        $sid                        = Session::get('wpworkorder.sid', 0);
        $applyAddMemberAry          = Session::get('wpworkorder.applyAddMember', []);
        //????????????????????????????????????
        if($pid)
        {
            if($project_id)
            {
                $data = view_door_supply_whitelist_pass::where('e_project_id',$project_id);
            } else {
                $data = view_dept_member::where('be_dept_id',$work_dept_id);
            }
            $addworkerAry = $data->get();
            //dd($addworkerAry);
            //??????????????????
            if($request->has('addMemberAry') )
            {
                foreach ($request->addMemberAry as $key => $val)
                {
                    $applyAddMemberAry[$key]    = User::getName($key);
                }

                Session::put('wpworkorder.applyAddMember',$applyAddMemberAry);
            }
            //??????????????????
            if($request->has('delMember') && count($request->delMember))
            {
                foreach ($request->delMember as $key => $val)
                {
                    unset($applyAddMemberAry[$key]);
                }
                Session::put('wpworkorder.applyAddMember',$applyAddMemberAry);
            }
            //???????????????????????????
            if(count($applyAddMemberAry))
            {
                foreach ($addworkerAry as $key => $val)
                {
                    if(isset($applyAddMemberAry[$val->b_cust_id])) unset($addworkerAry[$key]);
                }
            }
        }
        $router     = $this->routerPost1;
        $submitbtn  = Lang::get('sys_btn.btn_70');
        $submit     = 'agreeY';
        //view????????????
        $hrefBack   = $this->hrefOrder;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle1; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($router,''),'POST',1,TRUE);

        //=====================================??????????????????????????????=======================================//
        if(!$isCheck)
        {
            //????????????
            $tBody = $heads = [];
            //table
            $table = new TableLib();
            //??????
            $unitName = ($permit_kind == 1)? 'permit_201' : 'permit_206';
            $heads[] = ['title'=>'No'];
            $heads[] = ['title'=>Lang::get($this->langText.'.'.$unitName),'style'=>'width:50%;']; //?????????
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_162')]; //??????
            $table->addHead($heads,0);

            if(count($addworkerAry))
            {
                foreach ($addworkerAry as $val)
                {
                    list($isOver,$UserEndDate) = b_supply_member_ei::isOver($val->b_cust_id,$edate);
                    $btn    = ($isOver)? HtmlLib::Color(Lang::get($this->langText.'.permit_10050',['edate'=>$UserEndDate]),'red',1) : $form->checkbox('addMemberAry['.$val->b_cust_id.']',$val->b_cust_id);
                    if($permit_kind == 2)
                    {
                        $name1        = b_supply::getName($val->b_supply_id);
                        $name2        = User::getName($val->b_cust_id);
                    } else {
                        $name1        = be_dept::getName($val->be_dept_id);
                        $name2        = User::getName($val->b_cust_id);
                    }
                    $tBody[] = [
                        '0'=>[ 'name'=> $btn,'b'=>1,'style'=>'width:20%;'],
                        '11'=>[ 'name'=> $name1],
                        '12'=>[ 'name'=> $name2],
                        '99'=>[ 'name'=> '' ]
                    ];
                }

            }
            $table->addBody($tBody);
            //????????????
            $html = $table->output();
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_158'),1);
            unset($table);
        }

        //=====================================??????????????????????????????=======================================//
        if(count($applyAddMemberAry))
        {
            //????????????
            $tBody = $heads = [];
            $no    = 1;
            //table
            $table = new TableLib();
            //??????
            $heads[] = ['title'=>'No'];
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_152'),'style'=>'width:50%;']; //??????
            $table->addHead($heads,0);
            foreach ($applyAddMemberAry as $key => $uname)
            {
                $btn     = $form->submit( Lang::get('sys_btn.btn_23'),'4','delMember['.$key.']');
                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '12'=>[ 'name'=> $uname],
                            '99'=>[ 'name'=> $btn ]
                ];
                $no++;
            }
            $table->addBody($tBody);
            //????????????
            $html = $table->output();
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_159'),1);
        }

        //??????
        $html = HtmlLib::Color(Lang::get($this->langText.'.permit_10043'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_14'));

        //Submit
        $submitDiv  = $form->submit($submitbtn ,'1',$submit).'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

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
        
            $("form").submit(function() {
                $("#agreeY").find("input[type=\'submit\']").prop("disabled",true);
            });
            
        });

        ';

        //-------------------------------------------//
        //  ??????
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }

    /**
     * ??????????????????
     * ???????????? ??????
     */
    public function AddDept(Request $request)
    {
        //?????? Session ??????
        $this->getBcustParam();
        $this->getMenuParam();
        //??????
        $js = $contents = '';
        $adddeptAry     = [];
        $isCheck        = 0;

        $permit_kind                = Session::get('wpworkorder.permit_kind', 0);
        $work_dept_id               = Session::get('wpworkorder.work_dept_id', 0);
        $charge_dept                = Session::get('wpworkorder.charge_dept', 0);
        $pid                        = Session::get('wpworkorder.pid', 0);
        $sid                        = Session::get('wpworkorder.sid', 0);
        $applyAddDeptAry            = Session::get('wpworkorder.applyAddDept', []);
        $extDeptAry                 = [];//[$this->user_dept,$work_dept_id,$charge_dept];
        //????????????????????????????????????
        if($pid)
        {

            $adddeptAry = be_dept::getSelect(1,1,0,'Y',1,0);
            //dd($adddeptAry);
            //??????????????????
            if($request->has('addDeptAry') )
            {
                foreach ($request->addDeptAry as $key => $val)
                {
                    $applyAddDeptAry[$key]    = be_dept::getName($key);
                }

                Session::put('wpworkorder.applyAddDept',$applyAddDeptAry);
            }
            //??????????????????
            if($request->has('delDept') && count($request->delDept))
            {
                foreach ($request->delDept as $key => $val)
                {
                    unset($applyAddDeptAry[$key]);
                }
                Session::put('wpworkorder.applyAddDept',$applyAddDeptAry);
            }
            //???????????????????????????
            if(count($applyAddDeptAry))
            {
                foreach ($adddeptAry as $key => $val)
                {
                    if(isset($applyAddDeptAry[$key])) unset($adddeptAry[$key]);
                }
            }
        }
        $router     = $this->routerPost2;
        $submitbtn  = Lang::get('sys_btn.btn_70');
        $submit     = 'agreeY';
        //view????????????
        $hrefBack   = $this->hrefOrder;
        $btnBack    = $this->pageBackBtn;
        $tbTitle    = $this->pageNewTitle2; //table header


        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($router,''),'POST',1,TRUE);

        //=====================================???????????????????????????=======================================//
        if(!$isCheck)
        {
            //????????????
            $tBody = $heads = [];
            //table
            $table = new TableLib();
            //??????
            $heads[] = ['title'=>'No'];
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_209')]; //????????????
            $table->addHead($heads,0);

            if(count($adddeptAry))
            {
                foreach ($adddeptAry as $dept_id => $dept)
                {

                    $btn    = (!in_array($dept_id,$extDeptAry))? $form->checkbox('addDeptAry['.$dept_id.']',$dept_id) : '';
                    $tBody[] = [
                        '0'=>[ 'name'=> $btn,'b'=>1,'style'=>'width:5%;'],
                        '11'=>[ 'name'=> $dept],
                        '99'=>[ 'name'=> '' ]
                    ];
                }

            }
            $table->addBody($tBody);
            $html = $table->output();
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_160'),1);
            unset($table);
        }

        //=====================================???????????????????????????=======================================//
        if(count($applyAddDeptAry))
        {
            //????????????
            $tBody = $heads = [];
            $no    = 1;
            //table
            $table = new TableLib();
            //??????
            $heads[] = ['title'=>'No'];
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_209')]; //????????????
            $table->addHead($heads,0);
            foreach ($applyAddDeptAry as $key => $uname)
            {
                $btn     = $form->submit( Lang::get('sys_btn.btn_23'),'4','delDept['.$key.']');
                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '12'=>[ 'name'=> $uname],
                            '99'=>[ 'name'=> $btn ]
                ];
                $no++;
            }
            $table->addBody($tBody);
            $html = $table->output();
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_161'),1);
        }

        //??????
        $html = HtmlLib::Color(Lang::get($this->langText.'.permit_10044'),'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_14'));

        //Submit
        $submitDiv  = $form->submit($submitbtn ,'1',$submit).'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

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
        
            $("form").submit(function() {
                $("#agreeY").find("input[type=\'submit\']").prop("disabled",true);
            });
            
        });

        ';

        //-------------------------------------------//
        //  ??????
        //-------------------------------------------//
        $retArray = ["title"=>$this->pageTitleMain,'content'=>$contents,'menu'=>$this->sys_menu,'js'=>$js];
        return view('index',$retArray);
    }
}
