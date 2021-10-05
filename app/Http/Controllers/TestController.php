<?php

namespace App\Http\Controllers;

use App\API\APP\D002118;
use App\Http\Traits\App\AppMenuAuthTrait;
use App\Http\Traits\App\AppMenuGroupTrait;
use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringHistoryTrait;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Engineering\ViolationComplainTrait;
use App\Http\Traits\Engineering\ViolationContractorTrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\MenuAuthTrait;
use App\Http\Traits\MenuTraits;
use App\Http\Traits\Push\PushGroupTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorInOutErrTrait;
use App\Http\Traits\Report\ReptDoorLogListTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorLogTrait;
use App\Http\Traits\Report\ReptPermit2Trait;
use App\Http\Traits\Report\ReptPermitListTrait;
use App\Http\Traits\Report\ReptPermitTrait;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Http\Traits\Supply\SupplyMemberLicenseTrait;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\WorkPermit\WorkCheckKindTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorklineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\CheckLib;
use App\Lib\FcmPusherLib;
use App\Lib\HTTCLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\App\app_menu;
use App\Model\App\app_menu_auth;
use App\Model\b_menu;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_contractor;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Engineering\et_traning;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_factory_e;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Supply\b_supply_rp_project_license;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_log_door_today;
use App\Model\View\view_project_factory;
use App\Model\View\view_supply_member;
use App\Model\View\view_supply_user;
use App\Model\View\view_used_rfid;
use App\Model\View\view_user;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_process_topic;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_permit_workitem_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_record1;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;
use \DTS\eBaySDK\OAuth\Services;
use \DTS\eBaySDK\OAuth\Types\GetUserTokenRestRequest;
use \Hkonnet\LaravelEbay\EbayServices;
use \DTS\eBaySDK\Shopping\Types;
use Illuminate\Http\Request;
use Config;
use Html;
use DB;
use Storage;
use Auth;
use stdClass;

set_time_limit(0);

class TestController extends Controller
{
    use DoorTrait,ReptDoorMenInOutTodayTrait,WorkPermitDangerTrait,WorkPermitWorkItemTrait,WorkPermitKindTrait;
    use WorkPermitTrait,ReptDoorLogTrait,ReptDoorMenInOutTodayTrait,SupplyTrait,WorkPermitProcessTopicTrait;
    use WorkPermitTopicOptionTrait,ReptDoorInOutErrTrait,ViolationComplainTrait,ViolationContractorTrait;
    use WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderProcessTrait,SupplyMemberIdentityTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait,WorkPermitWorkOrderTrait;
    use PushGroupTrait,WorkPermitWorkerTrait,WorkPermitWorkTopicTrait;
    use BcustTrait,PushTraits,WorkPermitProcessTrait,WorkPermitWorkTopicOptionTrait;
    use ReptPermit2Trait,ReptPermitTrait,ReptDoorCarInOutTodayTrait,ReptDoorFactoryTrait;
    use AppMenuGroupTrait,AppMenuAuthTrait,ReptDoorLogListTrait;
    use EngineeringTrait,EngineeringMemberTrait,EngineeringDeptTrait,EngineeringFactoryTrait;
    use FactoryDeviceTrait,EngineeringLicenseTrait,EngineeringCarTrait,EngineeringHistoryTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait,WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderlineTrait;
    use WorkPermitWorkItemCheckTrait,WorkPermitWorklineTrait,WorkCheckKindTrait;
    use ReptPermitListTrait,SupplyMemberTrait,SupplyMemberLicenseTrait,TraningMemberTrait ;
    /**
     * 建構子
     */
    public function __construct()
    {

    }

    /**
     * 顯示測試內容
     * @param Request $request
     */
    public function index(Request $request)
    {
        $test      = [];
        $test[]    = $request->test . '_' . time();
        $today     = date('Y-m-d');
        $now       = date('Y-m-d H:i:s');
        $startTime = microtime(true);

        switch ($request->test)
        {
            case '1':
                $test[]  = view_supply_user::getSupplyMemberInfo(2000000077);
                break;
            case '2':
                $work_id = 3221;
                $list_id = 359;
                //離線題目
//                $test[] = $this->getApiWorkPermitAllProcessTopic(1,[2]);
                //$test[] = $this->getApiWorkPermitAllProcessTopic(1,[3])[0]['topic'][9];
                //歷程
//                $test[] = $this->getMyPermitWorkOrderProcess($work_id,$list_id)[3]['permit'][9];
//                $test[] = wp_work_process::getListProcess(255,0,2);;
//                $test[] = $this->getMyWorkPermitProcessTopicAns(1523,5857,2);;
                //檢點階段-承攬商安全檢點
                //$test[] = $this->getApiWorkPermitProcessTopic(1,3,$work_id,'',[3],'N',0)[11];
                $test[] = $this->getApiWorkPermitProcessTopic(1,3,$work_id,'',[3],'N',0);
                //檢點階段-轄區人員安全檢點
                //$test[] = $this->getApiWorkPermitProcessTopic(1,4,$work_id,'',[2,9],'N',0);
                //暫停階段-申請復工
//                $test[] = $this->getApiWorkPermitProcessTopic(1,24,$work_id,'',[3,9],'N',0);
                //施工中
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[9],'N',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[3],'N',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[2],'N',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[1],'N',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[9],'Y',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[3],'Y',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[2],'Y',0);
//                $test[] = $this->getApiWorkPermitProcessTopic(1,7,2788,'',[1],'Y',0);
                break;
            case '3':
                //測試APP 登入取得參數[職員身分] 2000006089
                $test['app_menu'] = app_menu_auth::getAppAuthMenuData(2);//職員
                $test['search']['all_store']            = b_factory::getApiSelect(0);
                $test['search']['b_factory_a']          = b_factory_a::getApiSelect();
                $test['search']['b_factory_b']          = b_factory_b::getApiSelect();
                $test['search']['b_factory_d']          = b_factory_d::getApiSelect();
                $test['search']['b_factory_e']           = b_factory_e::getApiSelect(0,0);
                $test['search']['project']              = e_project::getApiSelect();//工程案件
                $test['search']['project_type']         = e_project_type::getApiSelect();//工程案件分類
                $test['search']['project_aproc']        = SHCSLib::getCode('ENGINEERING_APROC',1,1); //進度
                $test['search']['violation']            = e_violation::getApiSelect();
                $test['search']['violation_law']        = e_violation_law::getApiSelect();
                $test['search']['violation_type']       = e_violation_type::getApiSelect();
                $test['search']['violation_punish']     = e_violation_punish::getApiSelect();
                $test['search']['shift']                = wp_permit_shift::getSelect(0,0,1);
                $test['search']['itemwork']             = $this->getApiWorkPermitKind(1);
                break;
            case '4':
                //測試APP 登入取得參數[承攬商身分]
                $b_supply_id = 190;
                $test['app_menu']                       = app_menu_auth::getAppAuthMenuData(3);//承攬商
                $test['search']['all_store']            = view_project_factory::getSupplyLocal($b_supply_id,1);
                $test['search']['b_factory_a']          = b_factory_a::getApiSelect();
                $test['search']['b_factory_b']          = b_factory_b::getApiSelect();
                $test['search']['b_factory_d']          = b_factory_d::getApiSelect();
                $test['search']['project']              = e_project::getApiSelect($b_supply_id);//工程案件分類
                $test['search']['project_type']         = e_project_type::getApiSelect();//工程案件分類
                $test['search']['project_aproc']        = SHCSLib::getCode('ENGINEERING_APROC',1,1); //進度
                $test['search']['violation']            = e_violation::getApiSelect();
                $test['search']['violation_law']        = e_violation_law::getApiSelect();
                $test['search']['violation_type']       = e_violation_type::getApiSelect();
                $test['search']['violation_punish']     = e_violation_punish::getApiSelect();
                $test['search']['shift']                = wp_permit_shift::getSelect(0,0,1);
                break;
            case '5':
                //D002001
                $searchAry       = [0,0,0,'','MEA0850009','',''];
                //$test[] = $this->getApiEngineeringList($searchAry,'N','','Y');
                $test[] = $this->getApiEngineeringMember(29);;
                $test[] = wp_permit_workitem_a::getSelect(29);
                break;
            case '6'://該人員違規是否存在
                $test = e_violation_contractor::isMemberExist(2100000008);
                break;
            case '7':
                $test[] = $this->getWorkPermitWorkOrderCheckRegular2();
                $test[] = $this->getWorkPermitWorkOrderCheckRegular3();
                $test[] = $this->getApiWorkPermitInReWorkProcess();
                break;
            case '8':
//                $test[] = $this->getProcessTopicOptionID(3,[],[7,9]);
//                $test[] = $this->getPermitWorkOrderProcessNeedImgUpload(39,37);
//                $test[] = $this->getLostImgTopic(39,3);
//                $test[] = SHCSLib::decode('MW1VYnRnbk1HaUxtekhqUlordjNPUT09');
                $selectAry1 = SHCSLib::genAllTimeAry('23:59','','30',1);
                $selectAry2 = SHCSLib::genAllTimeAry('08:00','00:00','30',1);
                $test['selectAry1'] = $selectAry1;
                $test['selectAry2'] = $selectAry2;
                break;
            case '9'://系統參數
                $wpSearch   = [0,33,'','',''];
                $storeSearch= [0,0,0];
                $depSearch  = [0,0,0,0,0,0];
                $dateSearch = ['','',''];
                $test[] = $this->getApiWorkPermitWorkOrderList(0,['W'],$wpSearch,$storeSearch,$depSearch,$dateSearch,['Y',0]);
                break;
            case '10':
                //
                $test[]  = $this->getPermitTodayFactoryTotal();
                $test[]  = $this->genPermitReptToday();
                break;
            case '11'://取得 工程案件的 門禁規則＋工作身分人員
                $test  = $this->updateWorkerMenReady(2931,2000002973,6,2,2000002973);
                break;
            case '12':
                //推播測試
                break;
            case '13':
                //門禁門別進出紀錄
                $test[]  = view_log_door_today::isExist(6,2000000738,1);
                break;
            case '14':
                $test[]  = wp_work::getTodayMyWork(2000001711);
//                $test[]  = e_project_license::getUserIdentityLicenseCode(22,2000001813,4);;
                break;
            case '15':
                //工作許可正
//                $test[] = $this->getApiWorkPermitAllProcessTopic(1);;
//                $test[] = $this->getApiWorkCheckTopic(6);//局限空間檢點表
                $test[] = $this->getApiWorkPermitProcessTarget(2931,2000004543,'Y');
                $test[] = $this->getApiWorkPermitProcessTopic(1,7,0,'',[9],'Y');
                //$test[] = HTTCLib::isTargetList(2697,2100000354,3);
                break;
            case '16':
                $test[]  = $this->getMyWorkPermitProcessTopicAns(3122,51790);
                break;
            case '17':
                //DOOR
                $work_id = 0;
                $project_id = 657;
                $b_factory_id = 6;
                $door_date = date('Y-m-d');
                // list($jobUserAry , $safeUserAry) = wp_work::getDoorRule($project_id);
                list($door_rule, $jobUserAry, $safeUserAry, $specialUserAry)  = e_project::getDoorRule($project_id);

                $adUserAry = array_merge($jobUserAry , $safeUserAry);

                // $test['door_rule']  = $door_rule;
                // $test['adUserAry']  = $adUserAry;
                // $test['jobUserAry']  = $jobUserAry;
                // $test['safeUserAry']  = $safeUserAry;
                // $test['specialUserAry']  = $specialUserAry;
                $test[]  = rept_doorinout_t::isExist($b_factory_id,$door_date,0,1,$adUserAry,$work_id);
                break;
            case '18':
                //SQL語法測試
                //$test[] = DB::getPdo()->query('EXEC [dbo].[USP_教育訓練白名單] @Date = N\'\',@et_course_id = N\'\'');
                //$test[] = DB::select('select b_cust_id,name,bc_type,isLogin from view_user where bc_type = :a and isLogin = :b;',['a'=>2,'b'=>'Y']);
                $test[] = DB::select('SELECT * FROM dbo.View_XX1(:a);',['a'=>'2020-01-19']);
                $testAry = \DB::table('log_paircard_lock')->where('isLock','Y')->pluck('b_cust_id')->toArray();
                $test[]  = $testAry;
                $test[]  = (in_array(2100000310,$testAry))? 1 : 0;
                break;
            case '19':
                //測試門禁DoorApi 帳號密碼
                $test[]  = CheckLib::isStoreDeviceToken('ABCZXF','L161231231');
                break;
            case '20':
                //R01010
                $test[] = $this->genDoorInOutFactoryApi(6,4,40,'');
                $test[]  = $this->getPermitTodayFactoryWorkerData('',6);
                //R01011
                $test[]  = $this->getDoorLogList(2);
                break;
            case '21':
                break;
            case '22':
                //$test[]  = $this->getPermitWorkTodayRept2(6);
                $test[]  = $this->genDoorInOutFactoryAppApi(6,0,0,'M',1);
                break;
            case '23':
                $doorStamp = date('Y-m-d H:i:s',(time() - 60));
                $test[]  = $doorStamp;
                $test[]  = LogLib::getRepeatDoor('2100000760',$doorStamp);
                break;
            case '24':
                $test[]  = LogLib::getTodayInputErrLog('M',6);
                $test[]  = LogLib::getTodayInputErrLog('M',6,0,23,0,'',0,2);
//                $test[]  = $this->genDoorInOutMenErrApi(1,18);
//                $test[]  = $this->genDoorInOutMenErrApi(2,18,2);
//                $test[]  = $this->getApiSupplyEngineering(1);
//                $test[]  = $this->getApiViolationContractorList();
                break;
            case '25':
                $test['project']        = view_door_supply_member::getProject('2100000008');
                $test['wp_permit_kind'] = $this->getApiWorkPermitKind(1);
                $test['danger_check'] = $this->getApiWorkPermitWorkOrderDangerCheckList(1);
                break;
            case '26':
                $test[]  = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect(120,['A'],0,1,1);
                break;
            case '27':
                //測試工作許可證 D002104
                $work_id    = 2706;
                $myTarget   = [1,9];
                //$test[]  = $this->getApiWorkPermitProcessTopic(1,1,$work_id,'',$myTarget,'N',0);
                //$test[]  = $this->getApiWorkPermitProcessTopic(1,2,$work_id);
                //$test[]  = $this->getApiWorkPermitCheckTopic(2718,27122);
                $test[]  = $this->getApiWorkPermitCheckTopic(2718,27147);
                break;
            case '28':
                $test[]  = $p1 = wp_permit_process::nextProcess(1,1,1,'A');
                $test[]  = $p2 = wp_permit_process::nextProcess(1,1,$p1,'A');
                $test[]  = $p3 = wp_permit_process::nextProcess(1,1,$p2,'A');
                $test[]  = $p4 = wp_permit_process::nextProcess(1,1,$p3,'A');
                $test[]  = $p5 = wp_permit_process::nextProcess(1,1,$p4,'A');
                $test[]  = $p6 = wp_permit_process::nextProcess(1,1,$p5,'A');
                $test[]  = $p7 = wp_permit_process::nextProcess(1,1,$p6,'A');
                $test[]  = $p8 = wp_permit_process::nextProcess(1,1,$p7,'A');
                $test[]  = $p9 = wp_permit_process::nextProcess(1,1,$p8,'A');
                $test[]  = $p10 = wp_permit_process::nextProcess(1,1,$p9,'A','','Y');
                $test[]  = $p11 = wp_permit_process::nextProcess(1,1,$p10,'A','','Y');
                break;

            //車輛白名單
            case '100':
                $store = $request->store ? $request->store : 1;
                list($supplyAry,$carnoAry,$updated_at) = $this->getCarWhiteList($store);
                $test['supply']  = $supplyAry;
                $test['carno']   = $carnoAry;
                $test['at']      = $updated_at;
                break;
            //車輛刷卡測試
            case '101':
                $car   = $request->car ? $request->car : 1;
                $store = $request->store ? $request->store : 1;
                $door  = $request->door ? $request->door : 1;
                $mode  = $request->mode ? $request->mode : 1;
                if($car && $door && $store && $mode)
                {
                    list($logid,$door_result,$door_memo,$isOnline,$door_type,$door_data) = $this->createDoorInoutRecord($car,$store,$door,$mode,$now,'');

                    $test['logid']          = $logid;
                    $test['door_result']    = $door_result;
                    $test['door_memo']      = $door_memo;
                    $test['isOnline']       = $isOnline;
                    $test['door_type']      = $door_type;
                    $test['door_data']      = $door_data;
                } else {

                    $test['err']   = 'car & store & door & mode ?';
                }
                break;
            //人員白名單
            case '102':
                list($whitelistAry,$rfidAry) = $this->getWhiteList();
                $test['whitelist']  = $whitelistAry;
                $test['rfidlist']   = $rfidAry;
                break;
            case '103':
                $user_id = $request->uid;
                $work_id = $request->wid;
                list($myDept,$be_title) = b_cust_e::getEmpInfo($user_id);//部門，簽核腳色
                $myDeptAry              = be_dept::getLevelDeptAry($myDept);
                $wp_work                = wp_work::getData($work_id);
                $dept1            = $wp_work->be_dept_id1;
                $dept2            = $wp_work->be_dept_id2;
                $dept3            = $wp_work->be_dept_id3;
                $dept4            = $wp_work->be_dept_id4;
                $dept5            = $wp_work->be_dept_id5;
                $test[] = '工單ID='.$work_id.'，使用者ID='.$user_id.'，部門ID='.$myDept.'，簽核腳色ID='.$be_title;
                $test[] = '管理的部門ID='.implode('，',$myDeptAry);
                $test[] = '轄區ID='.$dept1.'，監造ID='.$dept2.'，監工ID='.$dept3.'，會簽ID='.$dept4.'，上層部門ID='.$dept5;
                $test['(轄區)啟動階段-監造確認'] = HTTCLib::isTargetList($work_id,$user_id,1);
                $test['(承商)環境安全檢點-工安'] = HTTCLib::isTargetList($work_id,$user_id,3);
                $test['(轄區)環境安全檢點'] = HTTCLib::isTargetList($work_id,$user_id,4);
                $test['(轄區)連繫者'] = HTTCLib::isTargetList($work_id,$user_id,5);
                $test['(轄區)複檢者'] = HTTCLib::isTargetList($work_id,$user_id,6);
                $test['(轄區)A級會勘-監工'] = HTTCLib::isTargetList($work_id,$user_id,14);
                //$test['A級會勘-施工'] = HTTCLib::isTargetList($work_id,$user_id,16);
                $test['(轄區)A級會勘-轄區'] = HTTCLib::isTargetList($work_id,$user_id,17);
                $test['(轄區)會簽部門-轄區'] = HTTCLib::isTargetList($work_id,$user_id,12);
                $test['(轄區)轄區主簽者'] = HTTCLib::isTargetList($work_id,$user_id,13);
                $test['(轄區)轄區經理'] = HTTCLib::isTargetList($work_id,$user_id,15);
                $test['(轄區)申請收工'] = HTTCLib::isTargetList($work_id,$user_id,8);
                $test['(轄區)收工階段'] = HTTCLib::isTargetList($work_id,$user_id,9);
                break;
            //人員刷卡測試
            case '104':
                $bcid   = $request->bcid ? $request->bcid : 2000003235;
                $store = $request->store ? $request->store : 6;
                $door  = $request->door ? $request->door : 2;
                $mode  = $request->mode ? $request->mode : 2;
                if($bcid && $door && $store && $mode)
                {    

                    list($logid,$door_result,$door_result_kind,$door_result_name,$door_memo,$isOnline,$door_type,$resultAry) = $this->createDoorInoutRecord($bcid,$store,$door,$mode,$now,'');

                    $test['logid']               = $logid;
                    $test['door_result']         = $door_result;
                    $test['door_result_kind']    = $door_result_kind;
                    $test['door_result_name']    = $door_result_name;
                    $test['door_memo']           = $door_memo;
                    $test['isOnline']            = $isOnline;
                    $test['door_type']           = $door_type;
                    $test['resultAry']           = $resultAry;
                } else {

                    $test['err']   = 'bcid & store & door & mode ?';
                }
            break;

            //人員進出廠檢查
            case '137':
                // 取得 該工程案件 所屬 工地負責人＆工安人員 + 特殊人員
                $e_project_id = 595;
                $work_id = 0;

                list($door_rule, $jobUserAry, $safeUserAry, $specialUserAry)  = e_project::getDoorRule($e_project_id);
                $isSafeCheck = rept_doorinout_t::getExistAmt($b_factory_id = 6, $b_factory_d_id = 2, $door_date = $today, $safeUserAry, 1);
                $test['door_rule']           = $door_rule;
                $test['jobUserAry']          = $jobUserAry;
                $test['safeUserAry']         = $safeUserAry;
                $test['specialUserAry']      = $specialUserAry;
                $test['isSafeCheck']         = $isSafeCheck;

                $adUserAry = array_merge($jobUserAry, $safeUserAry);
                if (count($specialUserAry)) {
                    $adUserAry = array_merge($adUserAry, $specialUserAry);
                }
                $test['adUserAry']         = count($adUserAry);

                if ($work_id) {
                    $isWorkerExist = rept_doorinout_t::isExist($b_factory_id = 6, 0, $door_date = $today, 0, 1, $adUserAry, $work_id, $e_project_id);
                } else {
                    $isWorkerExist = rept_doorinout_t::isExist($b_factory_id = 6, $b_factory_d_id = 2, $door_date = $today, 0, 1, $adUserAry, NULL, $e_project_id);
                }
                $test['isWorkerExist']           = $isWorkerExist;
                if($isWorkerExist){
                    $isCanLevel = 1;
                }
                $test['isCanLevel']           = $isCanLevel;
                
                break;

            case '222':
                //[中介推播]
                $test[] = LogLib::getLogQueue();
                break;
            case '300':
                $count = 0;
                $startTime = time();
                $days       = sys_param::getParam('DELETE_LOG_DOOR_INOUT_DAYS',30); //TODO 還沒新增系統參數
                $today      = date('Y-m-d');
                $del_date   = date('Y-m-d',strtotime($today . '- '.$days.' days'));
                $add_log    = 0;

                //log_door_inout
                $res = DB::table('log_door_inout')->where('door_date','<=',$del_date)->pluck('id')->toArray();

                //log_door_inout_all
                $res2 = DB::table('log_door_inout_all')->where('door_date','<=',$del_date)->pluck('log_door_inout_id')->toArray();

                $array_diff = array_diff($res, $res2); //log_door_inout_all 缺少的紀錄
                if(count($array_diff)){
                    $log_door_inout_res = DB::table('log_door_inout')->whereIn('id',$array_diff)->get();
                    foreach ($log_door_inout_res as $row){
                        $INS = (array)$row;
                        $INS['log_door_inout_id'] = $row->id;
                        unset($INS['id']);
                        //$ret = DB::table('log_door_inout_all')->insert($INS);
                        //if($ret) $add_log += 1;
                    }
                }

                if(count($res)){
                    //$count = DB::table('log_door_inout')->whereIn('id',$res)->delete();
                }

                $test['today']      = $today;

                $endTime = time();
                $ret['cron_end'] = date('Y-m-d H:i:s', $endTime);
                $ret['cron_runtime'] = $endTime - $startTime;
                $test['add_log'] = '補紀錄'.$add_log.'筆';
                $test['del_count'] = '刪除'.$count.'筆';
                $test['del_date']   = $del_date;
                $test['log_count']  = $count;
                $test['msg']        = '補紀錄'.$add_log.'筆，刪除'.$count.'筆'.json_encode($ret);
                break;

            //白名單自動隨機刷卡
            case '333':
                // $queries = DB::getQueryLog();
                //安全機制，避免在正式主機啟動測試模式
                if ($_SERVER['SERVER_NAME'] != 'doortalin.cpc.com.tw') {

                    $test['startTime'] = date('Y-m-d H:i:s');
                    $max    = view_door_supply_whitelist_pass::count(); //白名單筆數
                    $num    = $request->num ? $request->num : 10; //筆數
                    if ($num > $max) {
                        $test['err'] = '操作失敗，超過白名單人數上限(' . $max . ')!';
                        break;
                    }

                    $member = view_door_supply_whitelist_pass::all()->random($num);
                    foreach ($member as $key => $val) {
                        $bcid  = $val->b_cust_id;
                        $store = $request->store ? $request->store : 6;
                        $door  = $request->door ? $request->door : 2;
                        $mode  = $request->mode ? $request->mode : 1;
                        if ($bcid && $door && $store && $mode) {
                            list($logid, $door_result, $door_result_kind, $door_result_name, $door_memo, $isOnline, $door_type, $resultAry) = $this->createDoorInoutRecord($bcid, $store, $door, $mode, $now, '');
                        } else {
                            $test['err'] = 'bcid & store & door & mode ?';
                        }
                    }
                    $test['endTime'] = date('Y-m-d H:i:s');
                    $test['num']   = '總共' . $num . '筆';
                }
                break;

            case '340':
                // 執行SP [USP_門禁儀表板同步刷卡紀錄]
                $FixRept = new FixReptDoorInOutQueue();
                $ret = $FixRept->handle();
                $test[] = $ret;
                break;

            case '1001':
                //[特殊作業]
                //快速產生帳號密碼
                $test[] = $this->chgBcustHasNotPwd();
            break;
            case '1002':
                //[特殊作業]
                //推播測試
                $uid    = ($request->uid)? $request->uid : 2100000680;
                $test[] = $this->pushToLoginSuccess($uid);;
            break;
            case '1004':
                //[特殊作業]
                //教育訓練白明單-舜源版本
                $test[] = \DB::getPdo()->query('EXEC [dbo].[USP_教育訓練白名單] @Date = N\'\',@et_course_id = N\'\'');

                break;
            case '1005':
                //[特殊作業]
                //產生推播
                $test[] = $this->pushToGroupTest();
                break;
            case '1006':
                // 增加測試執行 API 並顯示結果
                $jsonObj = new stdClass();
                $jsonObj->funcode = 'D002118';
                $api = new D002118($jsonObj, '127.0.0.1'); // toShow();
                $test[]  = $api->toShow();
                break;

            case '1012':
                // P01012 印卡模組_大林_承攬商成員基本資料
                $b_cust_id  = $request->b_cust_id ? $request->b_cust_id : 2000007839;
                $test[] = $b_cust_id;
                $test[] = !view_supply_paircard::isExist($b_cust_id);
                break;
                
            default:
                $test['msg'] = '請選擇測試函式「編號」';
                break;
        }
        //執行時間
        $endTime = microtime(true);
        $test['runtime'] = abs(($endTime - $startTime));
        dd($test);
    }


}
