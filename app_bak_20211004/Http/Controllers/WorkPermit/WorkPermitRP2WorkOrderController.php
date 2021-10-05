<?php

namespace App\Http\Controllers\WorkPermit;

use App\Http\Controllers\Controller;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
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
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
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
use App\Model\WorkPermit\wp_check;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Http\Request;
use Session;
use Lang;
use Auth;
use Html;

class WorkPermitRP2WorkOrderController extends Controller
{
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderProcessTrait;
    use WorkPermitWorkTopicTrait,WorkPermitWorkTopicOptionTrait;
    use WorkPermitWorkProcessTopicOption,WorkPermitProcessTrait,WorkPermitProcessTopicTrait;
    use WorkPermitTopicTrait,WorkPermitTopicOptionTrait,WorkPermitDangerTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitWorkImg,WorkPermitWorkOrderDangerTrait;
    use WorkPermitWorkOrderlineTrait;
    use PushTraits;
    /*
    |--------------------------------------------------------------------------
    | WorkPermitRP2WorkOrderController
    |--------------------------------------------------------------------------
    |
    | 工作許可證 「執行單」 啟動
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
        $this->hrefMain         = 'exa_wpworkorder2';
        $this->langText         = 'sys_workpermit';

        $this->hrefMainDetail   = 'exa_wpworkorder2/';
        $this->hrefMainDetail2  = 'edit_wpworkorder2/';
        $this->hrefMainNew      = 'new_wpworkorder2/';
        $this->routerPost       = 'postExaWpWorkOrder2';
        $this->routerPost2      = 'postEditWpWorkOrder2';

        $this->pageTitleMain    = Lang::get($this->langText.'.title24');//大標題
        $this->pageTitleList    = Lang::get($this->langText.'.list24');//標題列表
        $this->pageNewTitle     = Lang::get($this->langText.'.new24');//新增
        $this->pageEditTitle    = Lang::get($this->langText.'.edit24');//編輯

        $this->pageNewBtn       = Lang::get('sys_btn.btn_11');//[按鈕]新增
        $this->pageEditBtn      = Lang::get('sys_btn.btn_21');//[按鈕]編輯
        $this->pageBackBtn      = Lang::get('sys_btn.btn_5');//[按鈕]返回
        $this->pageNextBtn      = Lang::get('sys_btn.btn_37');//[按鈕]下一步

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
        //參數
        $out = $js ='';
        $no  = 0;

        $searchId   = 1;
        $today      = date('Y-m-d');
        $pid        = $request->pid;
        $aproc      = $request->aproc;
        $aprocAry   = SHCSLib::getCode('PERMIT_LIST_APROC',1,0,['C','B','A']);
        $worktime_process_id = sys_param::getParam('PERMIT_WORKTIME_CHARGE_PROCESS');
        $projectAry = $this->dept_project;
        //dd([$user_dept,$user_store,$projectAry]);
        if($request->has('clear'))
        {
            $pid = $aproc = 0;
            Session::forget($this->hrefMain.'.search');
        }
        if(!$pid)
        {
            $pid = Session::get($this->hrefMain.'.search.pid',0);
        } else {
            Session::put($this->hrefMain.'.search.pid',$pid);
        }
        if(!$aproc)
        {
            $aproc = Session::get($this->hrefMain.'.search.aproc','P');
        } else {
            Session::put($this->hrefMain.'.search.aproc',$aproc);
        }
        //view元件參數
        $tbTitle  = $this->pageTitleList;//列表標題
        $hrefMain = $this->hrefMain;
        //$hrefNew  = $this->hrefMainNew;
        //$btnNew   = $this->pageNewBtn;
        $hrefBack = $this->hrefMain;
        $btnBack  = $this->pageBackBtn;
        //-------------------------------------------//
        // 資料內容
        //-------------------------------------------//
        //抓取資料
        $storeid = 0;
        $deptid = $this->be_dept_id;
        //廠區主簽者
        if ($this->be_title_id == 4) {
            $storeid = $this->store_id; //如果沒有指定廠區，則限定自己負責的廠區
            $deptid = 0;  //負責整廠的工單
        }

        //抓取資料
        $aprocSearch= [$aproc];
        $wpSearch   = [0,$pid,'','',0];
        $storeSearch= [$storeid,0,0];
        $depSearch  = [$deptid,0,0,0,0,0];
        $dateSearch = [$today,'',''];

        $listAry = $this->getApiWorkPermitWorkOrderList(0,$aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch);
        $this->forget();
        if($request->has('showtest'))
        {
            dd($aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch,$listAry);
        }
        Session::put($this->hrefMain.'.Record',$listAry);
        Session::put($this->hrefMain.'.Search',[$aprocSearch,$wpSearch,$storeSearch,$depSearch,$dateSearch]);

        //dd($listAry);
        //-------------------------------------------//
        //  View -> Gird
        //-------------------------------------------//
        $form = new FormLib(0,$hrefMain,'POST','form-inline');
//        $form->addLinkBtn($hrefNew, $btnNew,2); //新增
        if(0)
        {
            $form->addLinkBtn($hrefBack, $btnBack,1); //返回
            $form->addHr();
        }
        $html = $form->select('pid',$projectAry,$pid,2,Lang::get($this->langText.'.permit_110'));
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
        if(!$searchId)
        {
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_111')]; //工程案號
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_110')]; //工程案件
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //監造部門
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')]; //轄區部門
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_106')]; //施工地點
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')]; //承商
            $heads[] = ['title'=>Lang::get($this->langText.'.permit_116')]; //件數
        } else {
            if($aproc == 'W')
            {
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //本證編號
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_113')]; //廠區
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //監造部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_104')]; //監造負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')]; //轄區部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_106')]; //施工地點
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')]; //承商
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_146')]; //工地負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_133')]; //進度
            } else {
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_112')]; //本證編號
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_3')]; //班別
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_103')]; //監造部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_104')]; //監造負責人
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_102')]; //轄區部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_106')]; //施工地點
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_101')]; //承商
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_146')]; //工地負責人&工安
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_138')]; //上一個階段
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_135')]; //目前階段
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_136')]; //目前負責部門
                $heads[] = ['title'=>Lang::get($this->langText.'.permit_133')]; //進度
            }
        }


        $table->addHead($heads,1);
        if(count($listAry))
        {
            $aprocColorAry = SHCSLib::getPermitAprocColor();
            $processColorAry = [4=>'#246B60',5=>'#C14C44',6=>'#AE3CB4',13=>'red',15=>'#BF4F40',14=>'#C4744F',17=>'#5D9933'];
            foreach($listAry as $value)
            {
                $no++;

                if(!$searchId)
                {
                    $id           = $value->e_project_id;
                    $name1        = $value->project_no; //
                    $name2        = $value->project; //
                    $name3        = $value->supply; //
                    $name4        = $value->amt; //
                    $name5        = $value->be_dept_id2_name; //
                    $name6        = $value->be_dept_id1_name; //
                    $name15       = $value->device; //

                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMain,'','pid='.SHCSLib::encode($id)),Lang::get('sys_btn.btn_37'),1); //按鈕

                    $tBody[] = ['0'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                                '2'=>[ 'name'=> $name2],
                                '5'=>[ 'name'=> $name5],
                                '6'=>[ 'name'=> $name6],
                                '3'=>[ 'name'=> $name3],
                                '4'=>[ 'name'=> $name4],
                                '99'=>[ 'name'=> $btn ]
                    ];
                } else {
                    $color11      = isset($processColorAry[$value->now_process_id])? $processColorAry[$value->now_process_id] : 'black';

                    $id           = $value->id;
                    $name1        = $value->permit_no; //
                    $name2        = HtmlLib::Color($value->supply,'red',1);
                    $name3        = $value->store .'<br/>'.$value->loacl.'<br/>'.$value->device; //
                    $name4        = $value->be_dept_id2_name; //
                    $name5        = $value->charge_user_name; //
                    $name7        = $value->be_dept_id1_name; //
                    $name6        = $value->supply_worker_name.'<br/>'.$value->supply_safer_name; //
                    $name8        = $value->aproc_name; //
                    $name9_time   = ($value->process_etime1) ? ('<br/>' . mb_substr($value->process_etime1, 11, 5)) : '';
                    $name9        = HtmlLib::Color($value->process_charger1 . $name9_time, 'black', 1); //
                    $name11       = HtmlLib::Color($value->now_process, $color11, 1);
                    $name12       = HtmlLib::Color($value->process_target2, 'red', 1);
                    $name13       = $value->isHoliday == 'Y' ? HtmlLib::Color(Lang::get($this->langText . '.permit_164'), 'red', 1) . '<br/>' : ''; //
                    $name14       = $name13 . $value->shift_name . '<br/><b>' . $value->wp_permit_danger . '</b>';
                    $name15        = $value->device; //
                    //$isColor      = $value->list_aproc_val == 'A' ? 5 : 2 ; //停用顏色
                    $isColor      = isset($aprocColorAry[$value->aproc]) ? $aprocColorAry[$value->aproc] : 2 ; //顏色


                    //按鈕
                    $btn          = HtmlLib::btn(SHCSLib::url($this->hrefMainDetail,$id),Lang::get('sys_btn.btn_21'),1); //按鈕
                    //按鈕：修改
                    $worktime_charger    = wp_work_process::getChargeUser($id,$worktime_process_id);
                    //如果身份是 該工作許可證負責審查施工時間的人
                    if($worktime_charger > 0 && $worktime_charger == $this->b_cust_id)
                    {
                        $btn .= HtmlLib::btn(SHCSLib::url($this->hrefMainDetail2,$id,'pid='.$request->pid),Lang::get('sys_btn.btn_38'),4); //按鈕
                    }
                    if($aproc == 'W')
                    {
                        $tBody[] = ['0'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                            '3' => ['name' => $name3],
                            '4' => ['name' => $name4],
                            '5' => ['name' => $name5],
                            '7' => ['name' => $name7],
                            '15' => ['name' => $name15],
                            '2' => ['name' => $name2],
                            '6' => ['name' => $name6],
                            '8' => ['name' => $name8, 'label' => $isColor],
                            '99' => ['name' => $btn]
                        ];
                    } else {
                        $tBody[] = ['0'=>[ 'name'=> $name1,'b'=>1,'style'=>'width:5%;'],
                            '14'=>[ 'name'=> $name14],
                            '4'=>[ 'name'=> $name4],
                            '5'=>[ 'name'=> $name5],
                            '7'=>[ 'name'=> $name7],
                            '15'=>['name' => $name15],
                            '2'=>[ 'name'=> $name2],
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
        $user_dept  = Session::get('user.bcuste.be_dept_id',-1);
        $sigan_id   = 0;
        $today      = date('Y-m-d');
        //清除不必要的session
        $this->forget();
        //view元件參數
        $hrefBack       = $this->hrefMain;//.'?pid='.$urlid;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //資料內容
        $getData        = $this->getData($id,$user_dept);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {
            //資料明細
            $A1         = $getData->wp_permit_id; //
            $A2         = $getData->store; //
            $A3         = $getData->b_factory_id; //
            $A4         = $getData->sdate; //
            $A5         = $getData->be_dept_id2; //
            $A6         = $getData->be_dept_id2_name; //
            $A7         = $getData->b_factory_memo; ///
            $A9         = $getData->wp_permit_danger; //
            $A11        = $getData->charge_user_name; //
            $A12        = $getData->be_dept_id1_name; //
            $A13        = $getData->wp_permit_workitem_memo; //
            $A14        = $getData->permit_no; //
            $A15        = $getData->project.'：'.$getData->project_no; //
            $A16        = $getData->supply; //
            $A17        = $getData->supply_worker_name; //
            $A18        = $getData->supply_safer_name;
            $A19        = $getData->e_project_id; ///
            $A20        = be_dept::getParantDept($getData->be_dept_id1); ///
            $A21        = $getData->be_dept_id3_name; ///
            $A22        = $getData->be_dept_id4_name; ///
            $A23        = $getData->local; ///
            $A24        = $getData->list_id; ///
            $A25        = $getData->now_process; ///
            $A26        = $getData->process_target2; ///
            $A27        = $getData->wp_work_process_id; ///
            $A28        = $getData->aproc; ///
            $A29        = $getData->last_work_process_id; //
            $A30        = $getData->last_process; //
            $A31        = $getData->process_target1 ? ' <'.$getData->process_target1.'>' : ''; //
            $A32        = ($getData->process_charger1)? ($getData->process_charger1 .$A31) : ''; //
            $A33        = ($getData->process_stime1)? ($getData->process_stime1.'  ~  '.$getData->process_etime1) : ''; //
            $A34        = ($getData->now_look_process_id)? $getData->now_look_process_id : 0; //
            $A35        = $getData->device; //
            $A36        = $getData->door; //
            $A37        = $getData->shift_name; //
            $A38        = $getData->b_car_memo; //
//            list($last_process_id,$now_process_id)  = wp_work_list::getProcessIDList($A29);
            $A40        = ($getData->last_charger == $this->b_cust_id && in_array($getData->last_process,[4,5,6,14,16,17,18]))? Lang::get('sys_permit.permit_157',['name1'=>$A25]) : '';
            $A41        = ($A40)? 'showWarm("'.$A40.'");' : '';

            $A98        = ($getData->mod_user)? Lang::get('sys_base.base_10614',['name'=>$getData->mod_user,'time'=>$getData->updated_at]) : ''; //
            $A99        = ($getData->isClose == 'Y')? true : false;
            $A97        = $getData->isOvertime;
            $A96        = ($getData->isHoliday == 'Y')? true : false;
            list($local,$localGPSX,$localGPSY) = wp_work::getLocalGPS($id);

            //過期
            if($A4 != $today)
            {
                $A28 = 'F';
            }

            //危害告知
            $dangerAry = Session::get($this->hrefMain.'.dangerAry',[]);
            if(!count($dangerAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderDangerList($id);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->wp_permit_danger_id > 0)
                        {
                            $dangerAry[$val->wp_permit_danger_id] = $val->name;
                        }
                    }
                }
                Session::put($this->hrefMain.'.dangerAry',$dangerAry);
                Session::put($this->hrefMain.'.old_dangerAry',$dangerAry);
            }
            //檢點單
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
            //許可工作項目
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
                            $itemworkAry[$val->wp_permit_workitem_id] = $val->memo;
                        }
                    }
                }
                Session::put($this->hrefMain.'.itemworkAry',$itemworkAry);
                Session::put($this->hrefMain.'.old_itemworkAry',$itemworkAry);
            }
            //管線
            $linekAry = Session::get($this->hrefMain.'.lineAry',[]);
            if(!count($linekAry))
            {
                $idAry  = $this->getApiWorkPermitWorkOrderLineList($id);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->wp_permit_pipeline_id > 0)
                        {
                            $linekAry[$val->wp_permit_pipeline_id] = $val->memo;
                        }
                    }
                }
                Session::put($this->hrefMain.'.lineAry',$linekAry);
                Session::put($this->hrefMain.'.old_lineAry',$linekAry);
            }
            //工程身份 + 施工人員
            $identityMemberAry = [];
            $engineeringIdentityAry = b_supply_engineering_identity::getSelect(0);
            $idAry  = $this->getApiWorkPermitWorkerList($id,[]);
            if(count($idAry))
            {
                foreach ($idAry as $key => $val)
                {
                    if($val->user_id > 0)
                    {
                        $work_memo   = '';
                        $tmp = [];
                        $tmp['name']            = $val->user_id.' '.User::getName($val->user_id);
                        $tmp['aproc_name']      = $val->aproc_name;
                        $tmp['apply_type']      = $val->apply_type;
                        $tmp['apply_type_name'] = $val->apply_type_name;
                        $tmp['identity']        = isset($engineeringIdentityAry[$val->engineering_identity_id])? $engineeringIdentityAry[$val->engineering_identity_id] : '';
                        if(!in_array($A28,['A','B']))
                        {
                            //待啟動 顯示目前正在做什麼
                            if($A28 == 'W')
                            {
                                //是否已經在廠
                                list($isIn,$work_memo) = HTTCLib::getMenDoorStatus($A3,$val->user_id);
                                if(!$isIn) $work_memo  = HtmlLib::Color($work_memo,'red',1);
                            } else {

                                //顯示進場紀錄
                                $door_stime = !is_null($val->door_stime)? substr($val->door_stime,0,19) : '';
                                $door_etime = !is_null($val->door_etime)? substr($val->door_etime,0,19) : '';
                                $work_time  = !is_null($val->work_stime)? substr($val->work_stime,0,19) : '';
                                if($work_time) $work_time .= !is_null($val->work_etime)? '~'.substr($val->work_etime,0,19) : '';
                                if($door_stime) $work_memo = Lang::get('sys_base.base_40243',['time1'=>$door_stime,'time2'=>$door_etime]);
                                if($work_time) $work_memo .= Lang::get('sys_base.base_40249',['time3'=>$work_time]);

                            }
                        }

                        $tmp['work_time'] = $work_memo;

                        $identityMemberAry[$key] = $tmp;
                    }
                }
            }
//            dd($idAry,$identityMemberAry);
            //是否可以編輯
            $isEdit = 0;

            //判斷階段
            $topic    = $ansRecordAry = [];
            $isAgree  = 'N'; //是否可以審查
            $isAllow  = ($A5 == $user_dept)? 1 : 0; //是否可以審查
            $isReady  = $getData->isStart; //是否可以審查
            $isReject = $getData->isStop; //是否可以審查
            $isEditWorkTime = 'N'; //是否可以更改 時間（限定特定階段的使用者）
            $err_momo = $myTarget = '';
            $process_id = 0;
            //啟動階段
            if($getData->list_aproc_val == 'W')
            {
                if($isReady == 'N')
                {
                    $err_momo  = Lang::get('sys_workpermit.permit_11001');// 施工人員人數不足
                } else {
                    $err_momo  = Lang::get('sys_workpermit.permit_11005');// 承攬商尚未啟動
                }
            }
            elseif(in_array($getData->list_aproc_val,['P','O']))
            {
                //檢查資格是否可以做
                $this->workerAry        = [$getData->supply_worker,$getData->supply_safer];
                $this->process_id       = wp_work_process::getProcess($A27);
                $this->target           = wp_permit_process_target::getTarget($this->process_id);
                list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::isTargetList($getData->id,$this->b_cust_id); // 簽核權限判斷
                //list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::genPermitTarget($this->b_cust_id,$this->bc_type,$this->workerAry,$this->target,$getData->supply_worker,$getData->supply_safer,$getData->be_dept_id1,$getData->be_dept_id2,$getData->be_dept_id3,$getData->be_dept_id4,$A20);
                //dd([$this->b_cust_id,$this->bc_type,$this->target,$this->isOp,$this->myTarget,$this->myAppType,$getData->list_aproc]);
                $myTarget = implode(',',$this->myTarget);
                $process_id = $this->process_id;

                //這是你負責的階段
                if($this->isOp)
                {
                    //這個階段的題目
                    $topic  = $this->getApiWorkPermitProcessTopic($A1,$this->process_id,$id,'',$this->myTarget);
                    //dd($topic);
                    $isAgree = 'Y';
                    //如果有指定要看的階段
                    if($A34 && $A34 != $A29)
                    {
                        $ansRecordAry2 = $this->getMyWorkPermitProcessTopicAns($id,$A34);
                        $ansRecordAry  = array_merge($ansRecordAry2,$ansRecordAry);
                    }
                    //上一個階段題目
                    $ansRecordAry = $this->getMyWorkPermitProcessTopicAns($id,$A29);
                    if($request->has('showtest'))
                    {
                        dd($ansRecordAry);
                    }
                    //全部<已經作答的題目>
                    //$ansRecordAry = $this->getApiWorkPermitWorkTopicRecord($id,$A24);
                } else {
                    //不是你負責的階段
                    $err_momo  = Lang::get('sys_workpermit.permit_11003',['name1'=>$A25,'name2'=>$A26]);// 啟動執行
                }
            }elseif(in_array($getData->list_aproc_val,['R'])) {
                $worktime_process_id = sys_param::getParam('PERMIT_WORKTIME_CHARGE_PROCESS');
                $worktime_charger    = wp_work_process::getChargeUser($id,$worktime_process_id);
                //如果身份是 該工作許可證負責審查施工時間的人
                if($worktime_charger > 0 && $worktime_charger == $this->b_cust_id)
                {
                    $isEditWorkTime = 'Y';
                }

            } else {
                //dd($getData->list_aproc_val);
            }
        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost,$id),'POST',1,TRUE);
        //本證編號
        $html = $A14;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_112'));
        //假日
        $html = $A96 ? HtmlLib::btn('#',Lang::get($this->langText.'.permit_164'),5) : '';
        $html.= HtmlLib::btn('#',$A37,2);
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_52'),1);
        //危險等級
        $html = '<b>'.$A9.'</b>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_3'),1);
        //工程案件
        $html = $A15;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_110'),1);
        //監造部門
        $html = $A6.'-'.$A11;
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
        //工作許可證
//        $html = wp_permit::getName($A1);
//        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_1'),1);
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
        $html = $A23.' / '.$A35;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_106'),1);
        //門別
        $html = $A36;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_163'),1);
        //工作地點說明
        $html = $A38;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_166'));
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
        //預計延時工作
        $html = $A97;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_128'));
        //危險等級
        $html = $A9;
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_3'),1);
        //工作項目
        $html = '<div id="workitemDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_108'),1);
        //檢點單
        $html = '<div id="checkDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_118'),1);
        //危害告知
        $html = '<div id="dangerDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_45'),1);
        //管線或設備之內容物
        $html = '<div id="lineDiv"></div>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_48'),1);
        //修改時間
//        if($isEditWorkTime == 'Y')
//        {
//            list($work_stime)    = wp_work_topic_a::getTopicAns($id,120);
//            list($work_etime)    = wp_work_topic_a::getTopicAns($id,121);
//
//            $html  = $form->textStr('work_stime',$work_stime,Lang::get($this->langText.'.permit_50'),'timeclock').'<br/>';
//            $html .= $form->textStr('work_etime',$work_etime,Lang::get($this->langText.'.permit_51'),'timeclock').'<br/>';
//            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_49'),1);
//        }
        //審查事由
        if($isReject == 'Y' && in_array($getData->list_aproc_val,['A','R']))
        {
            //檢點單
            $html = $form->textarea('reject_memo','',Lang::get('sys_base.base_10948'));
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_130'));
        }
        //最後異動人員 ＋ 時間
        $form->addHr();

        //工作許可證題目-上一階段答案
        if(count($ansRecordAry))
        {
            $form->addHr();

            foreach ($ansRecordAry as $value)
            {
                $topic_type = isset($value['topic_type'])? $value['topic_type'] : 0;
                $topicName  = isset($value['name'])? $value['name'] : '';
                //危險告知
                if($topic_type == 4)
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val)
                        {
                            $topic_ans[$val['topic_a_id']]['ans'] = '=';
                            $topic_ans[$val['topic_a_id']]['ans_type'] = $val['ans_type'];
                            $ansCheckbox = ($val['ans_value'] == '已告知')? 'Ｖ' : '=';
                            $html .= $ansCheckbox.$val['name'].$val['context'].'<br/>';
                        }
                    }
                    $form->add('nameT101',ContentLib::genSolidBox(Lang::get($this->langText.'.permit_45'),$html,1,3),$value['name']);
                }
                //簽名
                elseif($topic_type == 3)
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val)
                        {
                            //簽名
                            if($val['wp_option_type'] == 6)
                            {
                                $img   = ($val['ans_value'])? Html::image($val['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                $html .=  $img.'<br/>';
                            }elseif($val['wp_option_type'] == 5){
                                $html .= $val['name'].'<br/>';
                            }elseif($val['wp_option_type'] == 8) {
                                //GPS
                                $gps   = ($val['ans_value'])? $val['ans_value'] : '' ;
                                if($gps)
                                {
                                    $gpsAry = explode(',',$gps);
                                    $GPSX   = isset($gpsAry[0])? $gpsAry[0] : 0;
                                    $GPSY   = isset($gpsAry[1])? $gpsAry[1] : 0;

                                    //2019-12-30 新增 工作地點跟 施工地點 距離
                                    $distanceStr = '';
                                    if($localGPSX && $localGPSY && $GPSX && $GPSY && $GPSX != '0.0' && $GPSY != '0.0')
                                    {
                                        //工作地點：距離指定施工區域「:name1」:name2公尺  ！
                                        $distance = SHCSLib::getGPSDistance($localGPSX,$localGPSY,$GPSX,$GPSY,2);
                                        $distanceStr = Lang::get('sys_base.base_10942',['name1'=>$local,'name2'=>$distance]).'<br/>';
                                        $distanceStr = HtmlLib::Color($distanceStr,'blue',1);
                                    } else {
                                        if(!$localGPSX || !$localGPSY)
                                        {
                                            $distanceStr .= Lang::get('sys_base.base_10943').'<br/>';
                                        }
                                        if(!$GPSX || $GPSX == '0.0' || !$GPSY || $GPSY == '0.0')
                                        {
                                            $distanceStr .= Lang::get('sys_base.base_10944').'<br/>';
                                        }
                                        $distanceStr = HtmlLib::Color($distanceStr,'red',1);
                                    }

                                    $html .=  $distanceStr;

                                    $html .=  HtmlLib::genMapIframe($GPSX,$GPSY).'<br/>';
                                }
                            }

                        }
                    }
                    $form->add('nameT102',$html,$topicName);
                }
                //一般文字<多選>
                else
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val1)
                        {
                            $topic_a_id         = isset($val1['topic_a_id'])? $val1['topic_a_id'] : 0;
                            $ans_value          = isset($val1['ans_value'])? $val1['ans_value'] : '';
                            $wp_option_type     = isset($val1['wp_option_type'])? $val1['wp_option_type'] : 1;
                            $wp_check_id        = isset($val1['wp_check_id'])? $val1['wp_check_id'] : 0;
                            $topic_name         = $val1['name'].((!strpos('：',$val1['name']))? '：' : '');
                            //if($work_process_id == 49738 && $value['topic_id'] == 10) dd($work_process_id,$value);
                            if($wp_option_type == 1)
                            {
                                $html .= $topic_name.HtmlLib::Color($ans_value,'blue',1).'<br/>';
                            }
                            elseif(in_array($wp_option_type,[2,4,3,10,13,14,17,18]))
                            {
                                if(!is_string($ans_value)) $ans_value = '';
                                //if($ans_value == '施工人員(人數、姓名或另附名冊)：') dd();
                                if($topic_a_id == 120)
                                {
                                    $ans_value = date('H:i',strtotime($ans_value));
                                }
                                if($topic_a_id == 121)
                                {
                                    $ans_value = date('H:i',strtotime($ans_value));
                                }
                                $html .= $topic_name.HtmlLib::Color($ans_value,'blue',1).'<br/>';
                            }
                            //檢點單
                            elseif($wp_option_type == 9){
                                //table
                                $heads      = $tBody1 = $tBody2 = $option = [];
                                $check_kind     = wp_check::getKindID($wp_check_id);
                                $topicOptinType = ($check_kind == 1)? [2,4] : [];
                                //$optionAry = isset($val1['check']['option'])? $val1['check']['option'] : [];
                                //if($work_process_id == 49854 && $value['topic_id'] == 2) dd($work_process_id,$val1['check']);

                                if($check_kind == 1)
                                {
                                    //table顯示
                                    $table  = new TableLib();;
                                    //標題
                                    foreach ($val1['check'] as $val2)
                                    {
                                        foreach ($val2['option'] as $val3)
                                        {
                                            $optiontmp = [];
                                            $check_topic_a_option_type = $val3['wp_option_type'];
//                                                if($check_topic_a_option_type == 7) dd('Y',$val3);
//                                                if($work_process_id == 49854 && $val3['check_topic_a_id'] == 25) dd($val3,$val3['wp_option_type']);

                                            if(in_array($check_topic_a_option_type,$topicOptinType))
                                            {
                                                $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                                $optiontmp['ans_type']           = $val3['ans_type'];
                                                $option[] = $optiontmp;

                                                //時間格式
                                                if($val3['wp_option_type'] == 4)
                                                {
                                                    $heads[]  = ['title'=>$val3['name']];
                                                    $tBody1[] = ['name'=>''];
                                                    $tBody2[] = ['name'=>$val3['ans_value']];
                                                }
                                                //數值格式
                                                if($val3['wp_option_type'] == 2)
                                                {
                                                    //if($boxTitle == '施工階段')  dd($val2);
                                                    $heads[] = ['title'=>$val3['name']];
                                                    $checkColor = '';
                                                    if($val3['safe_action'] == 'between')
                                                    {
                                                        if(($val3['safe_limit1'] > $val3['ans_value']) || $val3['safe_limit2'] < $val3['ans_value'])
                                                        {
                                                            $checkColor = 'red';
                                                        }
                                                    }
                                                    if($val3['safe_action'] == 'more')
                                                    {
                                                        if($val3['safe_limit1'] > $val3['ans_value'])
                                                        {
                                                            $checkColor = 'red';
                                                        }
                                                    }
                                                    if($val3['safe_action'] == 'under')
                                                    {
                                                        if(($val3['safe_limit1'] < $val3['ans_value']))
                                                        {
                                                            $checkColor = 'red';
                                                        }
                                                    }
                                                    //if(count($otherAry)) dd($otherAry);
                                                    $tBody1[] = ['name'=>$val3['safe_val']];
                                                    $tBody2[] = ['name'=>HtmlLib::Color($val3['ans_value'],$checkColor,1)];
                                                }
                                            }
                                        }
                                    }

                                    $table->addHead($heads,0);
                                    $table->addBody([$tBody1,$tBody2]);
                                    $html .= $table->output();
                                }
                                foreach ($val1['check'] as $val2)
                                {
                                    foreach ($val2['option'] as $val3)
                                    {
                                        $optiontmp = [];
                                        $check_topic_a_option_type = $val3['wp_option_type'];
//                                                if($check_topic_a_option_type == 7) dd('Y',$val3);
//                                                if($work_process_id == 49854 && $val3['check_topic_a_id'] == 25) dd($val3,$val3['wp_option_type']);

                                        if(!in_array($check_topic_a_option_type,$topicOptinType))
                                        {
                                            $optiontmp['check_topic_a_id']   = $val3['check_topic_a_id'];
                                            $optiontmp['ans_type']           = $val3['ans_type'];
                                            $name3                           = $val3['name'];
                                            $ans_value3                      = isset($checkAry[$val3['ans_value']])? $checkAry[$val3['ans_value']] : $val3['ans_value'];
                                            $ans_value3                      = HtmlLib::Color($ans_value3,'blue',1);
                                            $option[] = $optiontmp;

                                            if(in_array($check_topic_a_option_type,[6,7])) {
                                                //圖片
                                                $img   = ($val3['ans_value'])? Html::image($val3['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                                $html .=  $name3.$img.'<br/>';
                                            }
                                            elseif($check_topic_a_option_type == 5) {
                                                //純顯示
                                                $html .=  '<b>'.$name3.'</b><br/>';
                                            }
                                            //數值格式
                                            else
                                            {
                                                $html .=  $name3.$ans_value3.'<br/>';
                                            }
                                        }
                                    }
                                }


                            } elseif($wp_option_type == 7) {
                                //圖片
                                $img   = ($val1['ans_value'])? Html::image($val1['ans_value'],'',['class'=>'img-responsive','height'=>'30%']) : '';
                                $html .=  $img.'<br/>';
                            } elseif($wp_option_type == 8) {
                                //GPS
                                $gps   = ($val1['ans_value'])? $val1['ans_value'] : '' ;
                                if($gps)
                                {
                                    $gpsAry = explode(',',$gps);
                                    $GPSX   = isset($gpsAry[0])? $gpsAry[0] : 0;
                                    $GPSY   = isset($gpsAry[1])? $gpsAry[1] : 0;

                                    //2019-12-30 新增 工作地點跟 施工地點 距離
                                    $distanceStr = '';
                                    if($localGPSX && $localGPSY && $GPSX && $GPSY && $GPSX != '0.0' && $GPSY != '0.0')
                                    {
                                        //工作地點：距離指定施工區域「:name1」:name2公尺  ！
                                        $distance = SHCSLib::getGPSDistance($localGPSX,$localGPSY,$GPSX,$GPSY,2);
                                        $distanceStr = Lang::get('sys_base.base_10942',['name1'=>$local,'name2'=>$distance]).'<br/>';
                                        $distanceStr = HtmlLib::Color($distanceStr,'blue',1);
                                    } else {
                                        if(!$localGPSX || !$localGPSY)
                                        {
                                            $distanceStr .= Lang::get('sys_base.base_10943').'<br/>';
                                        }
                                        if(!$GPSX || $GPSX == '0.0' || !$GPSY || $GPSY == '0.0')
                                        {
                                            $distanceStr .= Lang::get('sys_base.base_10944').'<br/>';
                                        }
                                        $distanceStr = HtmlLib::Color($distanceStr,'red',1);
                                    }

                                    $html .=  $distanceStr;
                                    $html .=  HtmlLib::genMapIframe($GPSX,$GPSY).'<br/>';
                                }
                            } else {
                                $html .= $topic_name.'<br/>';
                            }
                        }
                    }
                    $form->add('nameT102',$html,$topicName);
                }

            }
        }

        //工作許可證題目-作答
        if(is_array($topic) && count($topic))
        {
            //--- 作答區 ---//
            $html = HtmlLib::genBoxStart(Lang::get($this->langText.'.sub_title2'),2);
            $form->addHtml( $html );

            $topic_ans = [];
            if($request->showtest && $request->showtest == 'httc@123') dd($topic);
            foreach ($topic as $value)
            {
                //危險告知
                if($value['topic_type'] == 4)
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val)
                        {
                            $topic_ans[$val['topic_a_id']]['ans'] = '=';
                            $topic_ans[$val['topic_a_id']]['ans_type'] = $val['ans_type'];
                            $html .= $form->checkbox('topic['.$val['topic_a_id'].']','Y').($val['name'].$val['context']).'<br/>';
                        }
                    }
                    $form->add('nameT101',$html,$value['name']);
                }
                //簽名
                elseif($value['topic_type'] == 3)
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val)
                        {
                            //簽名
                            if($val['wp_option_type'] == 6)
                            {
                                if($this->sign_url)
                                {
                                    $html.= Html::image($this->sign_url,'',['class'=>'img-responsive','height'=>'30%']);
                                    $html.= $form->hidden('topic['.$val['topic_a_id'].']',$this->sign_img);
                                    $topic_ans[$val['topic_a_id']]['ans']       = $this->sign_img;
                                    $topic_ans[$val['topic_a_id']]['ans_type']  = $val['ans_type'];
                                } else {
                                    $html.= HtmlLib::Color(Lang::get($this->langText.'.permit_10025'),'red',1);
                                }

                                //$html = $form->canvas();
                                //$html.= $form->hidden('topic['.$val['topic_a_id'].']','');
                                //$sigan_id = $val['topic_a_id'];
                            }elseif($val['wp_option_type'] == 5){
                                $html .= $val['name'].'<br/>';
                            }

                        }
                    }

                    $form->add('nameT102',$html,$value['name']);
                }
                //一般文字
                else
                {
                    $html = '';
                    if(count($value['option']))
                    {
                        foreach ($value['option'] as $key => $val)
                        {
                            $topic_a_id = $val['topic_a_id'];
                            $ans_select = (isset($val['ans_select']) && is_array($val['ans_select']) )? $val['ans_select'] : [];
                            $topic_ans[$topic_a_id]['ans_type'] = $val['ans_type'];
                            $topic_ans[$topic_a_id]['ans']      = '';
                            if(in_array($val['wp_option_type'],[1,17]))
                            {
                                if($val['ans_type'] == 4)
                                {
                                    //開始時間
                                    if($topic_a_id == 120)
                                    {
                                        //$timeAry = SHCSLib::genAllTimeAry();
                                        $html .= $form->textStr('topic['.$topic_a_id.']',date('H:i'),$val['name'],'timeclock').'<br/>';
                                    } else {
                                        //結束時間 (日班:調整時間陣列至18:00，夜班:調整時間陣列從17:10~隔天早上 08:00)
                                        $shift_id               = $getData->wp_permit_shift_id;
                                        if($getData->wp_permit_shift_id == '2'){
                                            $timeAry = SHCSLib::genAllTimeAry('23:59','00:00','10',1);

                                            $selectAry = [];
                                            foreach ($timeAry as $key_t => $val_t) {
                                                $selectAry[$val_t['name']] = $val_t['name'];
                                            }

                                            //NOTE:暫時先用陣列寫死，有時間再調整function
                                            $outAry = [
                                                '08:10', '08:20', '08:30', '08:40', '08:50',
                                                '09:00', '09:10', '09:20', '09:30', '09:40', '09:50',
                                                '10:00', '10:10', '10:20', '10:30', '10:40', '10:50',
                                                '11:00', '11:10', '11:20', '11:30', '11:40', '11:50',
                                                '12:00', '12:10', '12:20', '12:30', '12:40', '12:50',
                                                '13:00', '13:10', '13:20', '13:30', '13:40', '13:50',
                                                '14:00', '14:10', '14:20', '14:30', '14:40', '14:50',
                                                '15:00', '15:10', '15:20', '15:30', '15:40', '15:50',
                                                '16:00', '16:10', '16:20', '16:30', '16:40', '16:50',
                                                '17:00'
                                            ];
                                            foreach ($outAry as $val_o) {
                                                $selectAry = array_except($selectAry, [$val_o, 'to', 'remove']);
                                            }

                                        }else{
                                            $selectAry = SHCSLib::genTimeAry('17:59',10);
                                        }
                                        
                                        $html .= $form->textSelect('topic['.$topic_a_id.']',$selectAry,'',$val['name'],'timeclock').'<br/>';
                                    }
                                }
                                elseif(in_array($topic_a_id,[122,123,124,14,42,164,165,167]) && count($ans_select)) // 顯示為下拉選單的題目 ID
                                {
                                    $selectAry = [];
                                    foreach ($ans_select as $val2)
                                    {
                                        $selectAry[$val2['id']] = $val2['name'];
                                    }
                                    $html .= $form->textSelect('topic['.$topic_a_id.']',$selectAry,'',$val['name']).'<br/>';

                                } else { // 其他題目則顯示為 checkbox
                                    $topic_ans[$topic_a_id]['ans'] = '=';
                                    $html .= $form->checkbox('topic['.$topic_a_id.']','Y').($val['name']).'<br/>';
                                }
                            }
                            elseif(in_array($val['wp_option_type'],[2,3]))
                            {
                                $html .= $form->textStr('topic['.$topic_a_id.']','',$val['name']).'<br/>';
                            }
                            elseif(in_array($val['wp_option_type'],[14]))
                            {
                                $html .= $form->textStr('topic['.$topic_a_id.']',$val['ans_value'],$val['name']).'<br/>';
                            }
                            elseif(in_array($val['wp_option_type'],[18]))
                            {

                            }
                            //檢點單
                            elseif($val['wp_option_type'] == 9){
                                //table
                                $heads  = $tBody1 = $tBody2 = $option = [];
                                $table  = new TableLib();
//                                dd($val);
                                $time   = '';
                                if(isset($val['check']))
                                {
                                    foreach ($val['check'] as $val1)
                                    {
                                        $topicTmp = 'topic['.$topic_a_id.'][check]['.$val1['check_topic_id'].']';
//                                        dd($val1);
                                        //標題
                                        foreach ($val1['option'] as $val2)
                                        {
                                            $optiontmp = [];
                                            //dd($val2,$val2['wp_option_type'],$val2['name']);

                                            if(in_array($val2['wp_option_type'],[2,4]))
                                            {
                                                $optiontmp['check_topic_a_id']   = $val2['check_topic_a_id'];
                                                $optiontmp['ans_type']           = $val2['ans_type'];
                                                $option[] = $optiontmp;

                                                //時間格式
                                                if($val2['wp_option_type'] == 4)
                                                {
                                                    $time = $form->hidden($topicTmp.'['.$val2['check_topic_a_id'].']',date('Y-m-d H:i:s'));
                                                }
                                                //數值格式
                                                if($val2['wp_option_type'] == 2)
                                                {
                                                    $heads[] = ['title'=>$val2['name']];
                                                    $inputClass = ($val2['ans_type'] == 2)? 'isNum' : '';
                                                    $otherAry = [];
                                                    if($val2['safe_action'] == 'between')
                                                    {
                                                        $otherAry  += ($val2['safe_limit1'])? ['min'=>$val2['safe_limit1']] : [];
                                                        $otherAry  += ($val2['safe_limit2'])? ['max'=>$val2['safe_limit2']] : [];
                                                    }
                                                    if($val2['safe_action'] == 'more')
                                                    {
                                                        $otherAry  += ($val2['safe_limit1'])? ['max'=>$val2['safe_limit1']] : [];
                                                    }
                                                    if($val2['safe_action'] == 'under')
                                                    {
                                                        $otherAry  += ($val2['safe_limit1'])? ['min'=>$val2['safe_limit1']] : [];
                                                    }
                                                    //if(count($otherAry)) dd($otherAry);
                                                    $tBody1[] = ['name'=>$val2['safe_val']];
                                                    $tBody2[] = ['name'=>$form->textStr($topicTmp.'['.$val2['check_topic_a_id'].']',$val2['ans_value'],'',$inputClass,$otherAry)];
                                                }
                                            }
                                        }
                                        $topic_ans[$topic_a_id]['ans']              = 'check';
                                        $topic_ans[$topic_a_id]['check_topic_id']   = $val1['check_topic_id'];
                                        $topic_ans[$topic_a_id]['option']           = $option;

                                        $table->addHead($heads,0);
                                        $table->addBody([$tBody1,$tBody2]);
                                        $html .= $table->output().$time;
                                    }
                                }

                            } elseif($val['wp_option_type'] == 7) {
                                //圖片
                                $html .= $form->file('topic['.$topic_a_id.']');
                                $html .= '<span id="topicsapn['.$topic_a_id.']" style="display: none;"><img id="topicimg['.$topic_a_id.']" src="#" alt="" width="240" /></span><br/>';
                            } else {
                                $html .= $val['name'].'<br/>';
                            }
                        }
                    }
                    $form->add('nameT102',$html,$value['name']);
                }

            }
            if($isReject == 'Y' || $isAgree == 'Y')
            {
                //檢點單
                $html = $form->textarea('reject_memo','',Lang::get('sys_base.base_10948'));
                $form->add('nameT3', $html,Lang::get($this->langText.'.permit_130'));
            }
            //Box End
            $html = HtmlLib::genBoxEnd();
            $form->addHtml($html);
            Session::put($this->hrefMain.'.Topic',$topic_ans);
        }

        //2019-12-16 主簽者可以修改 工作地點＆工作內容
        if($process_id == 13)
        {
            $addTxt = Lang::get($this->langText.'.permit_147');
            $addTxt = HtmlLib::Color($addTxt,'red',1);
            //工作地點說明
            $html = $form->textarea('local_memo2','','',$A7);
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_114').$addTxt);
            //工作項目說明
            $html = $form->textarea('workitem_memo2','','',$A13);
            $form->add('nameT3', $html,Lang::get($this->langText.'.permit_107').$addTxt);
        }

        //電子簽名不存在
        if(!$this->sign_url)
        {
            $isReady  = 'N';
            $isAgree  = 'N';
            $err_momo = Lang::get($this->langText.'.permit_10025');
        }
        //是否有錯誤訊息
        if(strlen($err_momo))
        {
            $html = HtmlLib::Color($err_momo,'red',1);
            $form->add('nameT98',$html,Lang::get($this->langText.'.permit_134'));
        }
        //Submit
        $submitDiv  = '';
        //電子簽名不存在
        if(!$this->sign_url)
        {
            $submitDiv .= $form->linkbtn(url('/myinfo'),Lang::get('sys_btn.btn_47'),'5','toUserSign').'&nbsp;';
        }
        if($isReject == 'Y')
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_61'),'5','agreeStop').'&nbsp;';
        }
//        if($isEditWorkTime == 'Y')
//        {
//            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
//        }
        if($isAgree == 'Y')
        {
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_1'),'1','agreeY').'&nbsp;';
            $submitDiv .= $form->submit(Lang::get('sys_btn.btn_2'),'5','agreeN').'&nbsp;';
        }
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$urlid);
        //$submitDiv.= $form->hidden('pid',$request->pid);
        $submitDiv.= $form->hidden('permit_id',$A1);
        $submitDiv.= $form->hidden('list_id',$A24);
        $submitDiv.= $form->hidden('now_process',$A25);
        $submitDiv.= $form->hidden('process_target2',$A26);
        $submitDiv.= $form->hidden('myTarget',$myTarget);
        $submitDiv.= $form->hidden('process_id',$process_id);
        $submitDiv.= $form->hidden('wp_work_process_id',$A27);
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

            '.$A41.'
            var canvas = document.getElementById("signature-pad");
            var signaturePad = new SignaturePad(canvas, {
              backgroundColor: "rgb(255, 255, 255)"
            });

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
            $(".timecolck").timePicker({
                pick12HourFormat: false
            });
            $("#stime,#etime").timepicker({
                showMeridian: false
            })
            $("#sdate").datepicker({
                format: "yyyy-mm-dd",
                startDate: "today",
                language: "zh-TW"
            });

        });

        $("input[name=\'headImg\']").change(function() {
              readURL(this);
              $("#blah_div").hide();
            });
        function readURL(input) {
          if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
              $("#blah").attr("src", e.target.result);
              $("#blah_div").show();
            }

            reader.readAsDataURL(input.files[0]);
          }
        }
        function showWarm(cont)
        {
            if(cont.length > 1)
            {
                alert(cont)
            }
        }
        function loadPage()
        {
            $.ajax({
                  type:"GET",
                  url: "'.url('/findPermitWorker2').'",
                  data: { type: 1, wid : '.$A1.', pid : '.$A19.',isCheck : '.$isEdit.', url : 3},
                  cache: false,
                  dataType : "text",
                  success: function(result){
                     $("#workitemDiv").html(result);
                  },
                  error: function(result){
                        //alert("ERR:1");
                  }
            });
            $.ajax({
                  type:"GET",
                  url: "'.url('/findPermitWorker3').'",
                  data: { type: 1, wid : '.$A1.', pid : '.$A19.',isCheck : '.$isEdit.', url : 3},
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
                  data: { type: 1, wid : '.$A1.', pid : '.$A19.',isCheck : '.$isEdit.', url : 3},
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
                  data: { type: 1, wid : '.$A1.', pid : '.$A19.',isCheck : '.$isEdit.', url : 3},
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
        elseif($request->has('agreeStop') || $request->has('agreeN'))
        {
            if(!$request->has('reject_memo') || !($request->reject_memo))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10148'))
                    ->withInput();
            }
        }
        elseif($request->has('agreeY'))
        {
            if(!$request->has('topic') || !count($request->topic))
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10130'))
                    ->withInput();
            }
            //如果該階段已被人提前完成
            list($charge_user,$charge_stamp) = wp_work_process::isCharge($request->wp_work_process_id);
            if($charge_user)
            {
                return \Redirect::back()
                    ->withErrors(Lang::get('sys_base.base_10160',['name'=>User::getName($charge_user),'time'=>$charge_stamp]))
                    ->withInput();
            }
        }
        $this->getBcustParam();
        $id         = SHCSLib::decode($request->id);
        $ip         = $request->ip();
        $list_id    = $request->list_id;
        $myTarget   = explode(',',$request->myTarget);
        $process_id = $request->process_id;
        $work_process_id = $request->wp_work_process_id;
        $menu       = $this->pageTitleMain;
        $isNew      = ($id > 0)? 0 : 1;
        $action     = ($isNew)? 1 : 2;
        $suc_msg    = 'base_10124';
        $err_msg    = 'base_10105';
        $now        = date('Y-m-d H:i:s');
        $allTopic   = Session::get($this->hrefMain.'.Topic',[]);
        $topicAry   = $request->topic;
        $backHref   = $this->hrefMain;

//        dd([$allTopic,$topicAry]);

        //新增
        if($isNew)
        {
            $ret = 0;
            $id  = $ret;
        } else {
            //停工階段
            if($request->has('agreeStop'))
            {
                $ImgData = ($this->sign_img)? @file_get_contents($this->sign_img) : '';
                $ret     = $this->stopWorkPermitWorkOrder($id,$request->reject_memo,$ImgData,$this->b_cust_id);
                $suc_msg    = 'base_10149';
                $err_msg    = 'base_10150';
                if($ret)
                {
                    //推播： 工作許可證停工通知->承攬商：工地負責人/安衛人員
                    $this->pushToSupplyPermitWorkStop($id,$this->b_cust_id,$request->reject_memo);
                }
            } else {
                //已啟動階段
                //題目<查無需要作答的題目>
                if(!count($allTopic))
                {
                    return \Redirect::back()
                        ->withErrors(Lang::get('sys_base.base_10133'))
                        ->withInput();
                }
                //轉換回答
                $ans = [];
                $reject_memo = $request->has('agreeN') ? $request->reject_memo : '';

                //2019-12-16 主簽者可以修改 工作內容＆工作地點
                if($request->local_memo2 || $request->workitem_memo2)
                {
                    $UPD = [];
                    $UPD['b_factory_memo2']          = $request->local_memo2;
                    $UPD['wp_permit_workitem_memo2'] = $request->workitem_memo2;
                    $this->setWorkPermitWorkOrder($id,$UPD,$this->b_cust_id);
                    //TODO:增加晚班到隔天的日期回寫edate
                }

                foreach ($allTopic as $key1 => $val1)
                {
                    //該工作許可證是否已經完工必填
                    if($request->has('agreeY')){
                        if($key1 == 93 && !isset($topicAry[$key1])){
                            return \Redirect::back()
                                ->withErrors(Lang::get('sys_base.base_10960'))
                                ->withInput();
                        }else if($key1 == 93 && $topicAry[$key1] == '='){
                            return \Redirect::back()
                                ->withErrors(Lang::get('sys_base.base_10960'))
                                ->withInput();
                        }
                    }
                    
                    //120.檢查開始時間的時間格式
                    if(isset($topicAry[$key1]) && $key1 == 120 && !CheckLib::isTime($topicAry[$key1]))
                    {
                        return \Redirect::back()
                            ->withErrors(Lang::get('sys_base.base_10158'))
                            ->withInput();
                    }


                    if(isset($topicAry[$key1]))
                    {
                        if($val1['ans_type'] != 5 && !in_array($val1['ans'],['check','sign']))
                        {
                            $getAns = !is_null($topicAry[$key1])? $topicAry[$key1] : '';
                            $ans[]  = ['topic_a_id'=>$key1,'ans'=>$getAns];
                        }
                        elseif($val1['ans_type'] == 5)
                        {
                            //處理圖片
                            $ImgFile    = $topicAry[$key1];
                            if($ImgFile && !is_string($ImgFile) && $ImgFile->isValid())
                            {
                                $extension = $ImgFile->getMimeType();
                                $filesize  = $ImgFile->getSize();

                                //dd([$topicAry[$key1],$extension]);
                                //[錯誤]格式錯誤
                                if(!in_array(strtoupper($extension),['IMAGE/JPEG','IMAGE/JPG','IMAGE/PNG','IMAGE/GIF'])){
                                    return \Redirect::back()
                                        ->withErrors($extension.Lang::get('sys_base.base_10119'))
                                        ->withInput();
                                } elseif($filesize > $this->fileSizeLimit1) {
                                    return \Redirect::back()
                                        ->withErrors(Lang::get('sys_base.base_10136',['limit'=>$this->fileSizeLimit2]))
                                        ->withInput();
                                } else {
                                    $ImgData = @file_get_contents($ImgFile->getRealPath());
                                }
                            } else {
                                //簽名
                                $ImgData = @file_get_contents($ImgFile);
                            }
                            $ans[]  = ['topic_a_id'=>$key1,'ans'=>(($ImgData)? base64_encode($ImgData) : '')];
                        }
                        elseif($val1['ans'] == 'check')
                        {
                            //檢點單內容
                            if(isset($topicAry[$key1]['check']))
                            {
                                foreach ($topicAry[$key1]['check'] as $k => $v)
                                {
                                    //dd($topicAry[$key1]['check']);
                                    $tmp    = [];
                                    $anstmp = [];
                                    $tmp['check_topic_id']  = $k;
                                    $tmp['record_stamp']    = $now;
                                    foreach ($v as $k2 => $v2)
                                    {
                                        $getAns   = ($v2 == 'now')? $now : (!is_null($v2)? $v2 : '');
                                        $anstmp[] = ['check_topic_a_id'=>$k2, 'ans'=>$getAns];
                                    }
                                    $tmp['option']          = $anstmp;
                                }

                                $ans[] = ['topic_a_id'=>$key1,'ans'=>[$tmp]];
                            }
                        }
                    } else {
                        if(!in_array($val1['ans'],['check','sign']))
                        {
                            $ans[] = ['topic_a_id'=>$key1,'ans'=>$val1['ans']];
                        }
                    }
//                    if(!$reject_memo && $this->setApiWorkPermitTopicRecord($myTarget,$id,$list_id,$work_process_id,$process_id,(object)$ans,'Y','N',$this->b_cust_id,$reject_memo))
//                    {
//                        return \Redirect::back()
//                            ->withErrors(Lang::get('sys_base.base_10173'))
//                            ->withInput();
//                    }
                }
                //dd([$allTopic,$topicAry,$myTarget,$id,$list_id,$work_process_id,$process_id,$ans]);
                //修改
                $ret = $this->setApiWorkPermitTopicRecord($myTarget,$id,$list_id,$work_process_id,$process_id,(object)$ans,'N','N',$this->b_cust_id,$reject_memo,'N');
                $suc_msg = 'base_10131';
                $err_msg = 'base_10132';
            }
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
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_list',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_process',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_process_topic',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_check_topic',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_check_topic_a',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_topic',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_topic_a',$id);
                LogLib::putLogAction($this->b_cust_id,$menu,$ip,$action,'wp_work_topic_a',$id);

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
     * 2019-11-20 允許 主簽者 異動施工起訖時間
     */
    public function EditWorkTime(Request $request,$urlid)
    {
        //讀取 Session 參數
        $this->getBcustParam();
        $this->getMenuParam();
        //參數
        $js = $contents ='';
        $id         = SHCSLib::decode($urlid);
        $user_dept  = Session::get('user.bcuste.be_dept_id',-1);
        //清除不必要的session
        $this->forget();
        //view元件參數
        $hrefBack       = $this->hrefMain;
        $btnBack        = $this->pageBackBtn;
        $tbTitle        = $this->pageEditTitle; //header
        //修改施工開始結束時間
        if($request->has('work_stime'))
        {
            //
            $this->setApiWorkPermitOrderWorkTime($request->id,$request->list_id,$request->work_stime,$request->work_etime);
            Session::flash('message',Lang::get('sys_base.base_10022'));
        }
        //資料內容
        $getData        = $this->getData($id,$user_dept);
        //如果沒有資料
        if(!isset($getData->id))
        {
            return \Redirect::back()->withErrors(Lang::get('sys_base.base_10102'));
        } else {

        }
        //-------------------------------------------//
        //  View -> Form
        //-------------------------------------------//
        //Form
        $form = new FormLib(1,array($this->routerPost2,$urlid),'POST',1,TRUE);
        list($work_stime)    = wp_work_topic_a::getTopicAns($id,120);
        list($work_etime)    = wp_work_topic_a::getTopicAns($id,121);

        $html  = $form->textStr('work_stime',$work_stime,Lang::get($this->langText.'.permit_50'),'timeclock').'<br/>';
        $html .= $form->textStr('work_etime',$work_etime,Lang::get($this->langText.'.permit_51'),'timeclock').'<br/>';
        $form->add('nameT3', $html,Lang::get($this->langText.'.permit_49'),1);
        //最後異動人員 ＋ 時間
        $form->addHr();

        //Submit
        $submitDiv  = '';
        $submitDiv .= $form->submit(Lang::get('sys_btn.btn_14'),'1','agreeY').'&nbsp;';
        $submitDiv .= $form->linkbtn($hrefBack, $btnBack,2);

        $submitDiv.= $form->hidden('id',$id);
        $submitDiv.= $form->hidden('list_id',$getData->list_id);
        //$submitDiv.= $form->hidden('pid',$request->pid);
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

            $(".timecolck").timePicker({
                pick12HourFormat: false
            });
            $("#stime,#etime").timepicker({
                showMeridian: false
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
        Session::forget($this->hrefMain.'.lineAry');
        Session::forget($this->hrefMain.'.old_lineAry');
        Session::forget($this->hrefMain.'.work_id');
        Session::forget($this->hrefMain.'.store_id');
    }
}
