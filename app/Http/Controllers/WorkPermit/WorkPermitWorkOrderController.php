<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Engineering\LicenseTrait;
use App\Http\Traits\Engineering\EngineeringTypeTrait;
use App\Http\Traits\Factory\FactoryTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
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
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_list;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;

class WorkPermitWorkOrderController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitWorkOrderlineTrait,WorkCheckTopicTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitWorkOrderController
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
        $this->hrefHome         = 'engineering';
        $this->hrefMain         = 'wpworkorder';
        $this->hrefProcess      = 'workpermitprocessshow';
        $this->langText         = 'sys_workpermit';
        $this->hrefPrint        = 'printpermit';

        $this->hrefMainDetail   = 'wpworkorder/';
        $this->hrefMainDetail9  = 'wpworkorderstop/';
        $this->hrefMainNew      = 'new_wpworkorder/';
        $this->routerPost       = 'postWpWorkOrder';
        $this->routerPost2      = 'wpworkorderCreate';

        $this->pageTitleMain    = Lang::get($this->langText.'.title20');//?????????
        $this->pageTitleList    = Lang::get($this->langText.'.list20');//????????????
        $this->pageNewTitle     = Lang::get($this->langText.'.new20');//??????
        $this->pageEditTitle    = Lang::get($this->langText.'.edit20');//??????

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[??????]??????
        $this->pageEditBtn      = Lang::get('sys_btn.btn_30');//[??????]??????
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
        $supplyAry  = b_supply::getSelect();
        $projectAry = e_project::getActiveProjectSelect();
        $storeAry   = b_factory::getSelect();
        $aprocAry   = SHCSLib::getCode('PERMIT_APROC',1);
        $selfAry    = SHCSLib::getCode('YES',1);

        $sid      = $request->sid;
        $aid      = $request->aid;
        $lid      = $request->lid;
        $aproc    = $request->aproc;
        $sdate    = $request->sdate;
        $self     = $request->self;
        $listAry  = [];
        if($request->has('clear'))
        {
            $sid = $aproc =0;
            $sdate = '';
            Session::forget($this->hrefMain.'.search');
        }
        if(!$sid)
        {
            $sid = Session::get($this->hrefMain.'.search.sid',0);
        } else {
            Session::put($this->hrefMain.'.search.sid',$sid);
        }
        if(!$aid)
        {
            $aid = Session::get($this->hrefMain.'.search.aid',0);
        } else {
            Session::put($this->hrefMain.'.search.aid',$aid);
        }
        if(!$lid)
        {
            $lid = Session::get($this->hrefMain.'.search.lid',0);
        } else {
            Session::put($this->hrefMain.'.search.lid',$lid);
        }
        if(!$sdate)
        {
            $sdate = Session::get($this->hrefMain.'.search.sdate',date('Y-m-d'));
        } else {
            Session::put($this->hrefMain.'.search.sdate',$sdate);
        }
        if(!$aproc)
        {
            $aproc = Session::get($this->hrefMain.'.search.aproc','');
        } else {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        }
        if(!$self)
        {
            $self = Session::get($this->hrefMain.'.search.self','Y');
        } else {
            Session::put($this->hrefMain.'.search.self',$self);
        }
        //view????????????
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.$Icon.$sdate;//????????????
        $hrefMain = $this->hrefMain;
        //-------------------------------------------//
        // ????????????
        //-------------------------------------------//
        //????????????
        if($lid)
        {
            $deptid     = ($self == 'Y')? $this->be_dept_id : 0;
            $wpSearch   = [$sid,$aid,'','',0];
            $storeSearch= [$lid,0,0];
            $depSearch  = [$deptid,0,0,0,0,0];
            $dateSearch = [$sdate,'',''];
            $listAry = $this->getApiWorkPermitWorkOrderList(0,$aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch);
            Session::put($this->hrefMain.'.Record',$listAry);
            Session::put($this->hrefMain.'.Search',[$aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch]);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
        $html = $form->select('lid',$storeAry,$lid,2,HtmlLib::Color(Lang::get($this->langText.'.permit_113'),'red',1));
        $html.= $form->select('sid',$supplyAry ,$sid,2,Lang::get($this->langText.'.permit_101'));
        $html.= $form->select('aid',$projectAry,$aid,2,Lang::get($this->langText.'.permit_110'));
        $form->addRowCnt($html);
        $html = $form->date('sdate',$sdate,2,Lang::get($this->langText.'.permit_115'));
        $html.= $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.permit_109'));
        $html.= $form->select('self',$selfAry,$self,2,Lang::get($this->langText.'.permit_149'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear','','');
        $form->addRowCnt($html);
        $html = HtmlLib::Color(Lang::get($this->langText.'.permit_10037'),'red',1);
        $form->addRow($html,4,1);
        $form->addHr();
        //??????
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')];     //????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_164')];     //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_110')];     //????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')];     //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_113')];     //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')];     //????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_104')];     //???????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')];     //????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_3')];       //????????????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_109')];     //??????
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_141')];     //??????

        $table->addHead($heads,1);
        if(count($listAry))
        {
            $aprocColorAry = SHCSLib::getPermitAprocColor();
            foreach($listAry as $value)
            {
                $proccessName = (in_array($value->aproc,['P','O']))? ($value->now_process.'<br/>'.$value->process_target2) : $value->now_process;

                $no++;
                $id           = $value->id;
                $name1        = $form->linkbtn($this->hrefPrint.'?id='.SHCSLib::encode($id), $value->permit_no,5,'','','','_blank'); //??????
                $name3        = $value->store.'<br/>'.$value->local.'<br/>'.$value->device.'<br/>'.HtmlLib::Color($value->door,'red',1); //
                $name4        = $value->be_dept_id1_name; //
                $name5        = $value->be_dept_id2_name; //
                $name7        = $value->charge_user_name.'<br/>'.$value->charge_user_tel; //
                $name6        = $value->wp_permit_danger.'<br/>'.$value->shift_name; //
                $name8        = (in_array($value->aproc,['P','R','O']))? $proccessName : $value->aproc_name; //
                $name10       = $value->project_no.' '.$value->project; //
                $name11       = $value->supply; //
                $name12       = $value->isHoliday == 'Y'? Lang::get($this->langText.'.permit_164') : ''; //
                $name12c      = $value->isHoliday == 'Y'? 5 : 1; //
                $list_id      = wp_work_list::isExist($id,1);
                $list_url     = ($list_id)? ('wid='.SHCSLib::encode($id).'&lid='.SHCSLib::encode($list_id)) : '';
//                $isColor      = $value->aproc == 'A' ? 4 : (in_array($value->aproc,['B','C','F'])? 5 : 2) ; //????????????
                $isColor      = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 2 ; //??????

                //??????
                $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_13'),1); //??????
                if($value->aproc == 'A')
                {
                    $btn2     = ''; //??????
                } else {
                    $btn2     = HtmlLib::btn(SHCSLib::url($this->hrefProcess,'',$list_url),Lang::get('sys_btn.btn_30'),4); //??????
                }

                $tBody[] = ['1'=>[ 'name'=> $name1],
                            '12'=>[ 'name'=> $name12,'label'=>$name12c],
                            '10'=>[ 'name'=> $name10],
                            '11'=>[ 'name'=> $name11],
                            '3'=>[ 'name'=> $name3],
                            '5'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name7],
                            '4'=>[ 'name'=> $name4],
                            '6'=>[ 'name'=> $name6],
                            '8'=>[ 'name'=> $name8,'label'=>$isColor],
                            '20'=>[ 'name'=> $btn2 ],
                            '99'=>[ 'name'=> $btn ]
                ];
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
                    $("#sdate").datepicker({
                        format: "yyyy-mm-dd",
                        language: "zh-TW"
                    });
                    $("#table1").DataTable({
                        "language": {
                        "url": "'.url('/js/'.Lang::get('sys_base.table_lan').'.json').'"
                    }
                    });
                } );
                ';

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
        //??????????????????session
        $this->forget();
        //??????
        $js = $contents ='';
        $id = SHCSLib::decode($urlid);
        Session::forget($this->hrefMain.'.identityMemberAry');
        Session::forget($this->hrefMain.'.itemworkAry');
        Session::forget($this->hrefMain.'.checkAry');
        $rootGroup  = sys_param::getParam('PERMIT_ROOT_USER_GROUP','');
        $rootGroupAry = explode(',',$rootGroup);
        $today      = date('Y-m-d');
        //view????????????
        $hrefBack       = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], strrpos($_SERVER['HTTP_REFERER'], '/') + 1) : $this->hrefMain.'?pid='.$urlid;
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
            $shift_id           = $getData->wp_permit_shift_id;
            if ($shift_id == 2) {
                $work_date_T1       = '18:00';
                $work_date_T2       = '24:00';
                $work_date_T3       = '01:00';
                $work_date_T4       = '08:00';
            } else {
                $work_date_T1       = '08:00';
                $work_date_T2       = '12:00';
                $work_date_T3       = '13:00';
                $work_date_T4       = '17:00';
            }

            //????????????
            $A1         = $getData->wp_permit_id; //
            $A2         = $getData->store.' > '.$getData->local.' > '.$getData->device; //
            $A3         = $getData->b_factory_id; //
            $A4         = $getData->sdate; //
            $A5         = $work_date_T1 . '~' . $work_date_T4; //??????????????????
            $A6         = $getData->be_dept_id1; //
            $A8         = $getData->b_factory_memo; //
            $A9         = $getData->wp_permit_danger; //
            $A13        = $getData->wp_permit_workitem_memo; //
            $A14        = $getData->be_dept_id1_name; //
            $A15        = $getData->be_dept_id2_name; //
            $A16        = $getData->charge_user_name; //
            $A17        = $getData->charge_user_tel? '-'.$getData->charge_user_tel : ''; //
            $A19        = $getData->e_project_id; //
            $A20        = $getData->supply; //
            $A21        = $getData->be_dept_id3_name;
            $A22        = $getData->be_dept_id4_name;
            $A23        = $getData->supply_worker_name; //
            $A24        = $getData->supply_safer_name; //
            $A28        = $getData->aproc; //
            $A29        = $getData->b_factory_a_id; //
            $A30        = $getData->permit_no; //
            $A31        = $getData->door; //
            $A37        = $getData->shift_name; //
            $A38        = $getData->wp_permit_shift_id; //
            $A39        = $getData->b_car_memo; //

            $A40        = $getData->aproc_name; //
            $A41        = $getData->aproc; //
            $A42        = $getData->charge_memo; //

            $A45        = $getData->apply_user.$getData->apply_user_name;
            $A46        = $getData->apply_stamp;
            //??????????????????
            $A50        = Lang::get($this->langText.'.permit_155',['name1'=>$A45,'name2'=>$A46]); //
            //?????????:??????
            $list_id    = wp_work_list::isExist($getData->id);
            list($charge_user,$charge_stamp) = wp_work_list::getApply($list_id);
            $A51        = ($A41 != 'A')? Lang::get($this->langText.'.permit_156',['name1'=>$charge_user,'name2'=>$charge_stamp]) : ''; //

            $isRoot     = ($this->isSuperUser)? 1 : 0;
            $isEdit     = 0;
            $isStop     = ($isRoot && in_array($A41,['W','P','K','R','O']))? 1 : 0;
            $isToday    = (in_array($A41,['A','W']) && strtotime($A4) >= strtotime($today))? 1 : 0;
            $isToday2   = (in_array($A41,['A','W','P','R','O']) && $A4  == $today)? 1 : 0;
            $isRootEdit = ($this->isSuperUser && $isToday)? 1 : 0;

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A96        = ($getData->isHoliday == 'Y')? true : false;

            //$localAry   = b_factory_e::getSelect(0,$A29,0);

            //????????????
            $dangerAry = Session::get($this->hrefMain.'.dangerAry',[]);
            if(!count($dangerAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderDangerList($id,1);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val['id'] > 0)
                        {
                            $dangerAry[$val['id']] = $val['name'];
                        }
                    }
                }
                Session::put($this->hrefMain.'.dangerAry',$dangerAry);
                Session::put($this->hrefMain.'.old_dangerAry',$dangerAry);
            }
            //?????????
            $checkAry = Session::get($this->hrefMain.'.checkAry',[]);
            if(!count($checkAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderCheckList($id,1);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val['id'] > 0)
                        {
                            $checkAry[$val['id']] = $val['name'];
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
                $idAry  = $this->getApiWorkPermitWorkOrderItemList($id,1);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val['id'] > 0)
                        {
                            $itemworkAry[$val['id']] = $val['memo'] ? $val['memo'] : $val['name'];
                        }
                    }
                }
                Session::put($this->hrefMain.'.itemworkAry',$itemworkAry);
                Session::put($this->hrefMain.'.old_itemworkAry',$itemworkAry);
            }
            //??????
            $linekAry = Session::get($this->hrefMain.'.lineAry',[]);
            if(!count($linekAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderLineList($id,1);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val['id'] > 0)
                        {
                            $linekAry[$val['id']] = $val['memo'];
                        }
                    }
                }
                Session::put($this->hrefMain.'.lineAry',$linekAry);
                Session::put($this->hrefMain.'.old_lineAry',$linekAry);
            }
            //????????????
            $identityMemberAry  = Session::get($this->hrefMain.'.identityMemberAry',[]);
            $identityMemberAry2 = [];
            if(!count($identityMemberAry))
            {
                $idAry  = $this->getApiWorkPermitWorkerList($id,[0]);
                if(count($idAry))
                {
                    $iidAry = [1=>'A',2=>'B'];
                    foreach ($idAry as $key => $val)
                    {
                        if($val->user_id > 0)
                        {
                            $iid = $val->engineering_identity_id;
                            $iid_head = (in_array($iid,[1,2]))? $iidAry[$iid] : str_pad($iid, 3, '0', STR_PAD_LEFT);
                            $identityMemberAry[$iid_head.$val->user_id] = $val->engineering_identity_id;
                            $tmp = [];
                            $tmp['door_stime'] = $val->door_stime;
                            $tmp['door_etime'] = $val->door_etime;
                            $tmp['work_time']  = ($val->work_stime)? ($val->work_stime .'~'.$val->work_etime) : '';
                            $identityMemberAry2[$iid_head.$val->user_id] = $tmp;
                        }
                    }
                }
                Session::put($this->hrefMain.'.work_id',$id);
                Session::put($this->hrefMain.'.store_id',$A3);
                Session::put($this->hrefMain.'.list_aproc',$A28);
                Session::put($this->hrefMain.'.isToday',$isToday2);
                Session::put($this->hrefMain.'.identityMemberAry',$identityMemberAry);
                Session::put($this->hrefMain.'.identityMemberAry2',$identityMemberAry2);
                Session::put($this->hrefMain.'.old_identityMemberAry',$identityMemberAry);
            }
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //
        $html = HtmlLib::Color($A30,'',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_112'));
        //
        $html = HtmlLib::Color($A40,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_109'));
        //????????????
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_115'),1);
        //??????????????????
        // dd($A5,$getData);
        $html = $A5;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_169'),1);
        //????????????
        if($isRootEdit)
        {
            $html = $form->checkbox('isHoliday','Y',$A96,'');
        } else {
            $html = $A96? HtmlLib::btn('#',Lang::get($this->langText.'.permit_164'),5) : '';
        }
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_164'),1);
        //??????
        if($isRootEdit)
        {
            $shiftAry = wp_permit_shift::getSelect();
            $html = $form->select('wp_permit_shift_id',$shiftAry,$A38);
        } else {
            $html = HtmlLib::btn('#',$A37,2);
        }
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_52'),1);
        //????????????
        if($isRootEdit)
        {
            $wpdangerAry  = SHCSLib::getCode('PERMIT_DANGER');
            $html = $form->select('wp_permit_danger_id',$wpdangerAry,$A9);
        } else {
            $html = '<b>'.$A9.'</b>';
        }
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_3'),1);
        //????????????
        $html = $A15.HtmlLib::Color($A51,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_103'),1);
        //???????????????
        $html = $A16.$A17;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_104'),1);
        //????????????
        $html  = $A21;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_132'));
        //????????????
        $html  = $A22;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_131'));
        //??????
        $html = $A20.HtmlLib::Color($A50,'red',1);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_101'),1);
        //???????????????
        $html = $A23;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_125'),1);
        //????????????
        $html = $A24;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_126'),1);
        //?????? > ?????? > ????????????
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_106'),1);
        //??????
        $html = $A31;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_163'),1);
        //????????????
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_102'),1);
        //????????????
        $html = $A39;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_166'));
        //??????????????????
        $html = $A8;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_114'));
        //??????????????????
        $html = $A13;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_107'));
        //????????????
        $html = '<div id="identityMemberDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_127'),1);
        //????????????
        $html = '<div id="workitemDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_108'),1);
        //?????????
        $html = '<div id="checkDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_118'),1);
        //????????????
        $html = '<div id="dangerDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_45'),1);
        //???????????????????????????
        $html = '<div id="lineDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_48'),1);

        if(in_array($A41,['B','C']))
        {
            //?????????
            $html = HtmlLib::Color($A42,'red',1);
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_130'),1);
        }
        if($isStop || $isRootEdit)
        {
            //????????????
            $html = $form->textarea('reject_memo');
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_165'),1);
        }

        //?????????????????? ??? ??????
        $html = $A98;
        $form->add('nameT98',$html,Lang::get('sys_base.base_10613'));

        //Submit
        $submitDiv = '';
        if($isEdit || $isRootEdit)
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        }
        if($isStop || $isRootEdit)
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_61'),'5','agreeStop').'&nbsp;';
        }
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
        //  View -> Javascript
        //-------------------------------------------//
        $js = '$(function () {
                    loadPage();
                });
        function loadPage()
            {
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermitWorker').'",
                      data: { type: 1, url : 2,wid : '.$A1.', pid : '.$A19.', isCheck : 0},
                      cache: false,
                      dataType : "text",
                      success: function(result){
                         $("#identityMemberDiv").html(result);
                      },
                      error: function(result){
                            //alert("ERR:1");
                      }
                });
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermitWorker2').'",
                      data: { type: 1, url : 2,isCheck : 0},
                      cache: false,
                      dataType : "text",
                      success: function(result){
                         $("#workitemDiv").html(result);
                      },
                      error: function(result){
                           // alert("ERR:1");
                      }
                });
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermitWorker3').'",
                      data: { type: 1,url : 2,isCheck : 0},
                      cache: false,
                      dataType : "text",
                      success: function(result){
                         $("#checkDiv").html(result);
                      },
                      error: function(result){
                            //alert("ERR:1");
                      }
                });
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermitWorker4').'",
                      data: { type: 1,url : 2,isCheck : 0},
                      cache: false,
                      dataType : "text",
                      success: function(result){
                         $("#dangerDiv").html(result);
                      },
                      error: function(result){
                            //alert("ERR:1");
                      }
                });
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermitWorker5').'",
                      data: { type: 1,url : 2,isCheck : 0},
                      cache: false,
                      dataType : "text",
                      success: function(result){
                         $("#lineDiv").html(result);
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
        //?????? Session ??????
        $this->getBcustParam();
        //????????????????????????????????????
//        $rootGroup  = sys_param::getParam('PERMIT_ROOT_USER_GROUP','');
//        $rootGroupAry = explode(',',$rootGroup);
//        if(!in_array($this->b_cust_id,$rootGroupAry))
//        {
//            $msg = Lang::get('sys_base.base_10151');
//            return \Redirect::back()->withErrors($msg);
//        }

        //???????????????
        if( $request->has('agreeStop') && !$request->reject_memo)
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10148'))
                ->withInput();
        }
        else {
            $this->getBcustParam();
            $id = SHCSLib::decode($request->id);
            $ip   = $request->ip();
            $menu = $this->pageTitleMain;
        }
        $isNew = ($id > 0)? 0 : 1;
        $action = ($isNew)? 1 : 2;

        $upAry = array();

        //??????
        if($id && $request->has('agreeY'))
        {
            $upAry['wp_permit_shift_id'] = ($request->wp_permit_shift_id) ? $request->wp_permit_shift_id : '';
            $upAry['wp_permit_danger']   = ($request->wp_permit_danger_id) ? $request->wp_permit_danger_id : '';
            $upAry['isHoliday']          = ($request->isHoliday) ? 'Y' : 'N';
            //dd($upAry);
            $suc_msg    = 'base_10104';
            $err_msg    = 'base_10103';

            $ret = $this->setWorkPermitWorkOrder($id,$upAry,$this->b_cust_id);
        }
        //??????
        if($request->has('agreeStop'))
        {
            $ret        = $this->stopWorkPermitWorkOrder($id,$request->reject_memo,'',$this->b_cust_id);
            $suc_msg    = 'base_10149';
            $err_msg    = 'base_10150';
            if($ret)
            {
                //????????? ???????????????????????????->???????????????????????????/????????????
                $this->pushToSupplyPermitWorkStop($id,$this->b_cust_id,$request->reject_memo);
            }
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

                //2-1-2 ?????? ????????????
                Session::flash('message',Lang::get('sys_base.'.$suc_msg));
                return \Redirect::to($this->hrefMain);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.'.$err_msg);
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
}
