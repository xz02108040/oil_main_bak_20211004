<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkOrderCheckRecord1Trait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkImg;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\CheckLib;
use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\HTTCLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\sys_code;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class WorkPermitRP3AddMemberController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderProcessTrait;
    use WorkPermitWorkTopicTrait,WorkPermitWorkTopicOptionTrait;
    use WorkPermitWorkProcessTopicOption,WorkPermitProcessTrait,WorkPermitProcessTopicTrait;
    use WorkPermitTopicTrait,WorkPermitTopicOptionTrait,WorkPermitDangerTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitWorkImg,WorkPermitWorkOrderDangerTrait,WorkOrderCheckRecord1Trait;
    use WorkPermitWorkerTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitRP3AddMemberController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 「補人單」 啟動
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
        $this->hrefHome         = 'wpworkorder';
        $this->hrefMain         = 'exa_wpworkorder3';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'exa_wpworkorder3/';
        $this->hrefMainNew      = 'new_wpworkorder3/';
        $this->routerPost       = 'postExaWpWorkOrder3';
        $this->routerPost2      = 'wpworkorderCreate3';

        $this->pageTitleMain    = Lang::get($this->langText.'.title27');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list27');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new27');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit27');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_21');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageNextBtn      = Lang::get('sys_btn.btn_37');//[按鈕]下一步

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
        $listAry = $aproc2 = [];

        //如果有登入，非總部
        $user_dept  = Session::get('user.bcuste.be_dept_id',-1);
        $user_store = b_factory_e::getStoreAry($user_dept);
        $pid        = SHCSLib::decode($request->pid);
        $project    = e_project::getName($pid);
        $sid        = $request->sid;
        $aproc      = $request->aproc;
        $today      = date('Y-m-d');
        $aprocAry   = SHCSLib::getCode('PERMIT_APROC',1,0,['A','C','B','O','F','W']);
        $projectAry = e_project::getEmpProject($user_store,0);

        //dd([$user_dept,$user_store,$projectAry]);
        if($request->has('clear'))
        {
            $sid = $aproc = 0;
            Session::forget($this->hrefMain.'.search');
        }
        if(!$sid)
        {
            $sid = Session::get($this->hrefMain.'.search.sid',0);
        } else {
            Session::put($this->hrefMain.'.search.sid',$sid);
        }
        if(!$aproc)
        {
            $aproc = Session::get($this->hrefMain.'.search.aproc','P');
        } else {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        }
        //view元件參數
        $Icon     = HtmlLib::genIcon('caret-square-o-right');
        $tbTitle  = $this->pageTitleList.($project ? $Icon.$project : '');//列表標題
        $hrefMain = $this->hrefMain;
        //$hrefNew  = $this->hrefMainNew;
        //$btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $project_id = ($pid)? $pid : $sid;
        $aprocSearch= [$aproc];
        $wpSearch   = [0,$project_id,'','',0];
        $storeSearch= [0,0,0];
        $depSearch  = [0,0,$this->be_dept_id,0,0,0];
        $dateSearch = [$today,'','Y'];
        $appSearch  = ['N',0];
        $isGroup    = ($pid && $aproc)? 'N' : 'Y';

        $listAry  = $this->getApiWorkPermitWorkOrderList(0,$aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch,$appSearch,$isGroup);
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.Search',[$aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch,$appSearch,$isGroup]);
        $this->forget();

        if($request->has('showtest'))
        {
            dd($aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch,$appSearch,$isGroup,$listAry);
        }
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
//        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if($pid)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
            $form->addHr();
        }
        $html = $form->select('sid',$projectAry,$sid,2,Lang::get($this->langText.'.permit_110'));
        $html.= $form->select('aproc',$aprocAry,$aproc,2,Lang::get($this->langText.'.permit_109'));
        $html.= $form->submit(Lang::get('sys_btn.btn_8'),'1','search');
        $html.= $form->submit(Lang::get('sys_btn.btn_40'),'4','clear');
        $form->addRowCnt($html);
        $form->addHr();
        //輸出
        $out .= $form->output(1);
        //table
        $table = new TableLib($hrefMain);
        //標題
        if(!$pid)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_111')]; //本證編號
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_110')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')]; //承商
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_117')]; //件數
        } else {
            if($aproc == 'W')
            {
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //本證編號
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_113')]; //廠區
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //監造部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_104')]; //監造負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')]; //轄區部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_125')]; //工地負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_126')]; //工安
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_133')]; //進度
            } else {
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //本證編號
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //監造部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_104')]; //監造負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')]; //轄區部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_125')]; //工地負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_126')]; //工安
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_135')]; //目前階段
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_136')]; //目前負責部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_133')]; //進度
            }
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            foreach($listAry as $value)
            {
                $no++;

                if(!$pid)
                {
                    $id           = $value->e_project_id;
                    $name1        = $value->project_no; //
                    $name2        = $value->headline; //
                    $name3        = $value->supply; //
                    $name4        = $value->amt; //

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['1'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                                '2'=>[ 'name'=> $name2],
                                '3'=>[ 'name'=> $name3],
                                '4'=>[ 'name'=> $name4],
                                '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $id           = $value->id;
                    $name1        = $value->permit_no; //
                    //$name2        = $value->sdate; //
                    $name3        = $value->store; //
                    $name4        = $value->be_dept_id2_name; //
                    $name5        = $value->charge_user_name; //
                    $name7        = $value->be_dept_id1_name; //
                    $name6        = $value->supply_worker_name; //
                    $name9        = $value->supply_safer_name; //
                    $name11       = $value->now_process;
                    $name12       = HtmlLib::Color($value->process_target2,'red',1);
                    $name8        = $value->list_aproc; //
                    $isColor      = $value->list_aproc_val == 'A' ? 5 : 2 ; //停用顏色

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainNew,$id,'pid='.$request->pid),Lang::get('sys_btn.btn_62'),1); //按鈕

                    if($aproc == 'W')
                    {
                        $tBody[] = ['1'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                            '3'=>[ 'name'=> $name3],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name7],
                            '6'=>[ 'name'=> $name6],
                            '9'=>[ 'name'=> $name9],
                            '8'=>[ 'name'=> $name8,'label'=>$isColor],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    } else {
                        $tBody[] = ['1'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name7],
                            '6'=>[ 'name'=> $name6],
                            '9'=>[ 'name'=> $name9],
                            '11'=>[ 'name'=> $name11],
                            '12'=>[ 'name'=> $name12],
                            '8'=>[ 'name'=> $name8,'label'=>$isColor],
                            '99'=>[ 'name'=> $btn ]
                        ];
                    }

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
     * 單筆資料 編輯
     */
    public function show(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents ='';
        $id         = SHCSLib::decode($urlid);
        $sigan_id   = $isCheck = 0;
        $today      = date('Y-m-d');
        //清除不必要的session
        $this->forget();
        //view元件參數
        $hrefBack       = $this->hrefMain.'?pid='.$request->pid;
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
            $A1         = $getData->wp_permit_id;
            $A2         = $getData->store;
            $A3         = $getData->b_factory_id;
            $A4         = $getData->sdate;
            $A5         = $getData->b_supply_id;
            $A6         = $getData->be_dept_id2_name;
            $A7         = $getData->b_factory_memo;
            $A9         = $getData->e_project_id;
            $A11        = $getData->charge_user_name? '-'.$getData->charge_user_name : '';
            $A12        = $getData->be_dept_id1_name;
            $A13        = $getData->wp_permit_workitem_memo;
            $A14        = $getData->permit_no;
            $A15        = $getData->project_name.'：'.$getData->project_no;
            $A16        = $getData->supply;
            $A17        = $getData->supply_worker_name;
            $A18        = $getData->supply_safer_name;
            $A21        = $getData->be_dept_id3_name;
            $A22        = $getData->be_dept_id4_name;
            $A23        = $getData->local;
            $A24        = $getData->list_id;
            $A25        = $getData->now_process;
            $A26        = $getData->process_target2;
            $A27        = $getData->wp_work_process_id;
            $A28        = $getData->aproc;
            $A31        = $getData->process_target1;
            $A32        = ($getData->process_charger1)? ($getData->process_charger1 .' <'.$A31.'>') : '';
            $A33        = ($getData->process_stime1)? ($getData->process_stime1.'  ~  '.$getData->process_etime1) : '';

            //工作許可證流程：過期，視同完成
            if($A4 != $today)
            {
                $A28 = 'F';
            }

            //工程身份 + 施工人員
            $identityMemberAry  = [];
            $addMemberAry       = $request->has('addMemberAry')? $request->addMemberAry : [];
            $IsAddMemberAry     = $request->has('addMemberAry')? 1 : 0;
            $engineeringIdentityAry = b_supply_engineering_identity::getSelect(0);
            $idAry  = $this->getApiWorkPermitWorkerList($id,[]);
            if(count($idAry))
            {
                foreach ($idAry as $key => $val)
                {
                    if(isset($identityMemberAry[$val->user_id]))
                    {
                        if(in_array($val->aproc,['A','P','R','O']))
                        {
                            $isOk = 1;
                        } else {
                            $isOk = 0;
                        }
                    } else {
                        $isOk = 1;
                    }
                    if($isOk)
                    {
                        $work_memo   = '';
                        $tmp = [];
                        $tmp['name']            = User::getName($val->user_id);
                        $tmp['aproc_name']      = $val->aproc_name;
                        $tmp['apply_user']      = $val->apply_user;
                        $tmp['apply_type']      = $val->apply_type;
                        $tmp['apply_type_name'] = $val->apply_type_name;
                        $tmp['apply_stamp']     = $val->apply_stamp;
                        $tmp['identity']        = isset($engineeringIdentityAry[$val->engineering_identity_id])? $engineeringIdentityAry[$val->engineering_identity_id] : '';
                        $tmp['identity_id']     = $val->engineering_identity_id;
                        if(!$IsAddMemberAry && $val->aproc == 'A')
                        {
                            $addMemberAry[$val->user_id] = $tmp;
                        }
                        if($A28 == 'W' && !count($addMemberAry))
                        {
                            //是否已經在廠
                            list($isIn,$work_memo) = HTTCLib::getMenDoorStatus($A3,$val->user_id);
                            if(!$isIn) $work_memo = HtmlLib::Color($work_memo,'red',1);

                        } else {

                            //顯示進場紀錄
                            $door_stime = !is_null($val->door_stime)? $val->door_stime : '';
                            $door_etime = !is_null($val->door_etime)? $val->door_etime : '';
                            $work_time  = !is_null($val->work_stime)? $val->work_stime : '';
                            if($work_time) $work_time .= !is_null($val->work_etime)? '~'.$val->work_etime : '';
                            if($door_stime) $work_memo = Lang::get('sys_base.base_40243',['time1'=>$door_stime,'time2'=>$door_etime]);
                            if($work_time) $work_memo .= Lang::get('sys_base.base_40249',['time3'=>$work_time]);

                        }
                        $tmp['work_time'] = $work_memo;

                        $identityMemberAry[str_pad($val->engineering_identity_id, 3, '0', STR_PAD_LEFT) . $val->user_id] = $tmp;
                    }
                }
            }
            if($request->has('showtest'))
            {
                dd($id,$idAry,$identityMemberAry,$addMemberAry);
            }
//

            //加人作業
            //可以添加的工程身份
            $identityAry    = wp_permit_identity::getSupplyIdentitySelect($A1,$A5,$A9);
            if($request->has('worker') && $request->has('identity_id'))
            {
                $tmp['name']            = User::getName($request->worker);
                $tmp['aproc_name']      = '';
                $tmp['apply_type']      = '';
                $tmp['apply_type_name'] = '';
                $tmp['apply_user']      = $this->name;
                $tmp['apply_stamp']     = date('Y-m-d H:i:s');
                $tmp['identity']        = isset($engineeringIdentityAry[$request->identity_id])? $engineeringIdentityAry[$request->identity_id] : '';
                $tmp['identity_id']     = $request->identity_id;

                $addMemberAry[$request->worker] = $tmp;
            }
            if($request->has('delMember') && count($request->delMember))
            {
                foreach ($request->delMember as $key => $val)
                {
                    $this->closeWorkPermitWorkerMen(0, $id, $key, 0, $this->b_cust_id);
                    unset($addMemberAry[$key]);
                }
            }

            //ROUTER POST
            if($request->has('nextY'))
            {
                if(!$IsAddMemberAry) {
                    return \Redirect::back()->withErrors(Lang::get('sys_base.base_10181'));
                }
                $isCheck = 1;
            }
            $router     = (!$isCheck)? $this->routerPost2 : $this->routerPost;
            $submitbtn  = (!$isCheck)? $this->pageNextBtn : Lang::get('sys_btn.btn_12');
            $submit     = (!$request->has('nextY'))? 'nextY' : 'agreeY';
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($router,$urlid),'POST',1,TRUE);
        //本證編號
        $html = $A14.'('.$isCheck.')';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_112'));
        //工程案件
        $html = $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_110'),1);
        //監造部門
        $html = $A6.$A11;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_103'),1);
        //監工部門
        $html  = $A21;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_132'));
        //會簽部門
        $html  = $A22;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_131'));
        //承商
        $html = $A16;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_101'),1);
        //工地負責人
        $html = $A17;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_125'),1);
        //安衛人員
        $html = $A18;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_126'),1);
        //施工日期
        $html = $A4;
        $form->add('nameT1', $html,Lang::get($this->langText.'.permit_115'),1);
        //廠區
        $html = $A2;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_113'),1);
        //廠區-轄區
        $html = $A12;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_102'),1);
        //廠區-場地
        $html = $A23;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_106'),1);
        //工作地點說明
        $html = $A7;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_114'));
        //工作項目說明
        $html = $A13;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_107'));
        //專業人員
        //table
        $table = new TableLib();
        $heads = $tBody = [];
        //標題
        $heads[] = ['title'=>'No'];
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_41')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_43')]; //成員
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_47')]; //狀態
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_46')]; //在廠狀態
        $table->addHead($heads,0);
        //table 內容
        if(count($identityMemberAry))
        {
            $no = 0;
            foreach ($identityMemberAry as $key => $val)
            {
                $no++;
                $name1        = $val['identity'];
                $name2        = $val['name'];
                $name3        = $val['work_time'];
                $name4s       = ($val['apply_type'] == 2)? '('.HtmlLib::Color($val['apply_type_name'],'red',1).')' : '';
                $name4        = $val['aproc_name'].$name4s;

                $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                            '11'=>[ 'name'=> $name1,'style'=>'width:20%;'],
                            '12'=>[ 'name'=> $name2,'style'=>'width:15%;'],
                            '14'=>[ 'name'=> $name4,'style'=>'width:15%;'],
                            '13'=>[ 'name'=> $name3]
                ];
            }
        }
        $table->addBody($tBody);
        $html = $table->output();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_127'),1);

        //審查區域
        $form->addHr();
        //補人作業
        //table
        $table = new TableLib();
        $heads = $tBody = [];
        //標題
        $heads[] = ['title'=>'No'];
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_151')]; //工程身份
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_152')]; //成員
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_153')]; //申請人
        $heads[] = ['title'=>Lang::get($this->langText.'.permit_154')]; //申請時間
        $table->addHead($heads,1);
        if(!$isCheck) {
            //加入
            $tBody[] = ['0' => ['name' => '', 'b' => 1, 'style' => 'width:5%;'],
                '11' => ['name' => $form->select('identity_id', $identityAry, '', 12)],
                '12' => ['name' => $form->select('worker', []), 'style' => 'width:25%;'],
                '13' => ['name' => ''],
                '14' => ['name' => ''],
                '99' => ['name' => $form->submit(Lang::get('sys_btn.btn_45'), '2', 'addMember')]
            ];
        }
        //加入成員
        if(count($addMemberAry))
        {
            $no    = 1;
            foreach ($addMemberAry as $key => $val)
            {
                if($key)
                {
                    $name1  = isset($val['identity'])? $val['identity'] : (isset($engineeringIdentityAry[$val])? $engineeringIdentityAry[$val] : '');
                    $name2  = isset($val['name'])? $val['name'] : User::getName($key);
                    $name3  = isset($val['apply_user'])? $val['apply_user'] : $this->name;
                    $name4  = isset($val['apply_stamp'])? $val['apply_stamp'] : date('Y-m-d');
                    $name5  = isset($val['identity_id'])? $val['identity_id'] : $val;
                    $btn    = $form->hidden('addMemberAry['.$key.']',$name5);
                    if(!$isCheck)
                    {
                        //按鈕
                        $btn         .= $form->submit( Lang::get('sys_btn.btn_23') ,'4','delMember['.$key.']'); //按鈕
                    }


                    $tBody[] = ['0'=>[ 'name'=> $no,'b'=>1,'style'=>'width:5%;'],
                                        '11'=>[ 'name'=> $name1],
                                        '12'=>[ 'name'=> $name2,'b'=>1],
                                        '13'=>[ 'name'=> $name3,'b'=>1],
                                        '14'=>[ 'name'=> $name4,'b'=>1],
                                        '99'=>[ 'name'=> $btn ]
                    ];
                    $no++;
                }
            }
        }

        $table->addBody($tBody);
        //專業人員
        $html = $table->output();
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_150'),1);
        //最後異動人員 ＋ 時間
        $form->addHr();


        //Submit
        $submitDiv  = '';
        $submitDiv .= $form->submit($submitbtn,'1',$submit).'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

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
            $("#agreeY").click(function (e) {
                if (signaturePad.isEmpty()) {
                    e.preventDefault();
                    return alert("'.Lang::get('sys_base.base_10134').'");
                } else {
                    var data = signaturePad.toDataURL("image/png");
                    $("#topic['.$sigan_id.']").val(data);
                    
                    alert('.$sigan_id.');
                }
            });
            
            $(".isNum").keypress(function (e) {
                 if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                    return false;
                 }
            });
            $(".isNum").change(function () {
                var num =$(this).val();
                var max =$(this).prop("max");
                var min =$(this).prop("min");
                if(num > 0)
                {
                    if(max > 0 && min > 0 && !(num >= min && num <= max))
                    {
                        //alert("1:"+num+",max:"+max+",min:"+min);
                        $(this).css("color","red");
                    }
                    else if(max > 0 && !$.isNumeric(min) && (num < max))
                    {
                        //alert("2:"+num);
                        $(this).css("color","red");
                    }
                    else if(min > 0 && !$.isNumeric(max) && (num > min))
                    {   
                        //alert("3:"+num);
                        $(this).css("color","red");
                    }
                } 
            });
            
            $( "#identity_id" ).change(function() {
                 var pid    = "'.$A9.'";
                 var wid    = "'.$id.'";
                 var iid    = $("#identity_id").val();
                $.ajax({
                      type:"GET",
                      url: "'.url('/findPermit').'",  
                      data: { type: 3, id : iid, pid : pid, wid : wid},
                      cache: false,
                      dataType : "json",
                      success: function(result){
                         $("#worker option").remove();
                         $.each(result, function(key, val) {
                            $("#worker").append($("<option value=\'" + key + "\'>" + val + "</option>"));
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
        if( !$request->id )
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.base_10103'))
                ->withInput();
        }
        else
        {
            //dd($request->all());
            //沒有任何人
            if(!$request->has('addMemberAry') || !count($request->addMemberAry))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10152'))
                    ->withInput();
            }
        }
        $this->getBcustParam();
        $id         = SHCSLib::decode($request->id);
        $ip         = $request->ip();
        $menu       = $this->pageTitleMain;
        $isNew      = ($id > 0)? 0 : 1;
        $action     = ($isNew)? 1 : 2;
        $now        = date('Y-m-d H:i:s');
        $backHref   = $this->hrefMain;
        //dd([$allTopic,$topicAry]);

        //檢查工作許可證 目前的階段是不可加人
        $aproc = wp_work::getAproc($id);
        $aprocErrAry = ['A'=>'base_10153','B'=>'base_10157','O'=>'base_10155','F'=>'base_10156','C'=>'base_10154','W'=>'base_10182'];
        //沒有任何人
        if(isset($aprocErrAry[$aproc]))
        {
            return \Redirect::back()
                ->withErrors(Lang::get('sys_base.'.$aprocErrAry[$aproc]))
                ->withInput();
        }


        //dd($upAry);
        //新增
        if($isNew)
        {
            $ret = 0;
            $id  = $ret;
        } else {
            //修改
            $ret = $this->addWorkPermitWorker($id,$request->addMemberAry,$this->b_cust_id);
            $suc_msg = 'base_10131';
            $err_msg = 'base_10132';
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_worker',$id);

                //2-1-2 回報 更新成功
                Session::flash('message',Lang::get('sys_base.'.$suc_msg));
                return \Redirect::to($backHref);
            }
        } else {
            $msg = (is_object($ret) && isset($ret->err) && isset($ret->err->msg))? $ret->err->msg : Lang::get('sys_base.'.$err_msg);
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
        $data = $this->getWorkPermitWorkOrder($uid);
        return (isset($data->id))? $data : $ret;
    }

    protected function forget()
    {
        Session::forget($this->hrefMain.'.identityMemberAry');
        Session::forget($this->hrefMain.'.old_identityMemberAry');
        Session::forget($this->hrefMain.'.itemworkAry');
        Session::forget($this->hrefMain.'.old_itemworkAry');
        Session::forget($this->hrefMain.'.checkAry');
        Session::forget($this->hrefMain.'.old_checkAry');
        Session::forget($this->hrefMain.'.dangerAry');
        Session::forget($this->hrefMain.'.old_dangerAry');
        Session::forget($this->hrefMain.'.work_id');
        Session::forget($this->hrefMain.'.store_id');
    }
}
