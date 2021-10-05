<?php

namespace App\Http\Traits\Factory;

use DB;
use Lang;
use Storage;
use App\Lib\LogLib;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\sys_code;
use App\Model\sys_param;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_work;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use Illuminate\Support\Facades\Log;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_s;
use App\Model\Report\rept_doorinout_t;
use App\Model\Report\rept_doorinput_t;
use App\Model\View\view_log_door_today;
use App\Model\View\view_project_factory;
use App\Model\WorkPermit\wp_work_worker;
use App\Model\View\view_door_supply_member;
use App\Model\Engineering\e_violation_contractor;

/**
 * 門禁管制
 *
 */
trait DoorTrait
{
    /**
     * 新增 教育訓練資格 通過名單紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createCoursePass($uid = 0)
    {
        $result = \DB::getPdo()->query('EXEC [dbo].[USP_教育訓練白名單] @Date = N\'\',@et_course_id = N\'\'');

        $retStr = Lang::get('sys_base.base_10127',['suc'=>0,'err'=>0,'json'=>$result]);
        return [0,$retStr];
    }

    /**
     * 新增 出入進刷卡 紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createDoorInoutRecord($bcid,$b_factory_id,$b_factory_d_id,$mode,$time,$img = '',$isDoorRecord = 'Y')
    {
        //===========================================================//
        //1. 參數
        //===========================================================//
        $logid              = $door_type = $lastDoorType = 0;
        $door_result        = 'N'; //進出結果
        $door_result_name   = \Lang::get('sys_base.base_10717');  //進出結果顯示名稱
        $door_result_kind   = 2;  //進出結果顯示名稱
        $retAry             = [];
        $specialUserAry     = [];
        $isUser             = 1;
        $isAdd              = 0;
        $isExist            = 0;
        $isLevel            = 0;
        $work_id            = 0;
        $isUT               = 0;
        $identity           = 0; //異常錯誤
        $errCode            = 0; //異常錯誤
        $workRootErr        = 0; //異常錯誤
        $door_rule          = 0; //異常錯誤
        $door_rule_kind     = 0; //異常錯誤
        $project_door_rule  = 0; //異常錯誤
        $lastDoorId         = 0;
        $isSupply           = 1;
        $bc_type            = 3;
        $b_supply_id        = 0;
        $b_rfid_id          = 0;
        $e_project_id       = 0;
        $jobkindName        = \Lang::get('sys_base.base_30130');
        $rfid_code          = '';
        $work_aproc         = '';
        $name               = '';
        $b_supply           = '';
        $project            = '';
        $lastDoorName       = '';
        $violation_memo     = ''; //違規事由
        $visit_memo         = ''; //訪客事由

        $resultAry = [];
        $resultAry[] = ['',''];
        $resultAry[] = ['',''];
        $jobUserAry = $safeUserAry = $workerInArr = $jobUserInAry = $safeUserInAry = [];

        $today          = date('Y-m-d'); //今日
        $door_date      = substr($time,0,10); //門禁日期
        $doorErrCodrAry = SHCSLib::getCode('DOOR_ERR_CODE');
        $identity_A     = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);//工地負責人
        $identity_B     = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);//工地負責人
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $doorResultAry  = SHCSLib::getCode('DOOR_INOUT_RESULT');
        $jobAry         = SHCSLib::getCode('JOB_KIND');
        $doorName       = b_factory_d::getName($b_factory_d_id);
        //1-1-1.離線時間判斷值：差距5分鐘 視為離線
        $door_min_time_limit        = sys_param::getParam('DOOR_MIN_TIME_LIMIT',300);
        //1-1-2.未來時間判斷值：差距1.5分鐘 視為未來
        $door_max_over_time_limit   = sys_param::getParam('DOOR_MAX_OVER_TIME_LIMIT',90);
        //1-1-3.2019-12-09 門禁本地端無法判斷 重複刷卡問題：重複刷卡時間上限
        $door_max_repeat_limit      = sys_param::getParam('DOOR_MAX_REPEAT_LIMIT',60);

        //1-3. 判斷 進出時間 視為 在線
        $isOnline  = ( strtotime($time) < (time() - $door_min_time_limit) )? 'N' : 'Y';
        $isOnline2 = ( strtotime($time) < (time()) )? 'N' : 'Y';

        $now = date('Y-m-d H:i:s');
        $bcid= str_replace('-','',$bcid);
        //1-4. 判斷 未來日期
        $isOver  = ($isOnline == 'Y' && (strtotime($time) > (time() + $door_max_over_time_limit)))? 'Y' : 'N';

        //
        $door_max_repeat_stamp = date('Y-m-d H:i:s',(time() - $door_max_repeat_limit));

        //===========================================================//
        //2. 白名單檢查
        //===========================================================//
        //2-1. 人員判斷：承攬商成員白名單
        $personAry      = (is_numeric($bcid)) ? DB::table('view_door_supply_whitelist_pass')->where('b_cust_id',$bcid)->first() : [];
        //2-1-1. 存在「人員」白名單
        if(isset($personAry->b_cust_id))
        {
            $isExist            = 1;
            $isSupply           = ($personAry->bc_type == 3)? 1 : 0;
            $e_project_id       = $personAry->e_project_id;
            $project            = $personAry->project;
            $b_supply_id        = $personAry->b_supply_id;
            $b_supply           = $personAry->supply;
            $name               = $personAry->name;
            $b_rfid_id          = $personAry->b_rfid_id;
            $rfid_code          = $personAry->rfid_code;
            $bc_type            = $personAry->bc_type;
            $bid                = $personAry->b_cust_id;
            $isUT               = $personAry->isUT;
            $project_door_rule  = $personAry->door_check_rule;
            $cpc_tag            = $personAry->cpc_tag;
            $jobkind            = $personAry->job_kind;
            if(in_array($jobkind,[1,3]) && $cpc_tag == 'A') $jobkind = 2;
            if(in_array($jobkind,[1,2])  && $cpc_tag == 'B') $jobkind = 3;
            if(in_array($jobkind,[1,2,3])  && $cpc_tag == 'E') $jobkind = 4;
            if(isset($jobAry[$jobkind]))$jobkindName = $jobAry[$jobkind];
            $door_result        = 'Y'; //可以進入
            $door_result_name   = \Lang::get('sys_base.base_10716');
            $door_result_kind   = 1;
            //2021-05-18 配合門禁工作站 離線判斷進出，必須一直上傳1/2
            if($isOnline == 'Y')
            {
                $mode = 0;
            }
            //[OnLine]判斷 是否可以進入該區域「警衛室所屬廠區」
            //dd($e_project_id,$b_factory_id,$b_factory_d_id);
            if($isOnline == 'Y' && !e_project_f::isExist($e_project_id,$b_factory_id))
            {
                $isExist = -1;
            }
            if($jobkind == 5)
            {
                $isExist = -3;
            }
        } elseif($isDoorRecord == 'C') {
            //2-2-1. 車輛判斷：車卡白名單
            $carAry = DB::table('view_door_car')->where('b_factory_id',$b_factory_id)->where('car_no',$bcid)->first();
//        dd($b_factory_id,$bcid,$carAry);
            //2-2-2. 存在「車輛」白名單
            if(isset($carAry->car_no))
            {
                $isExist        = 1; //白名單判斷
                $isSupply       = 1; //承攬商判斷
                $isUser         = 0; //人員車輛判斷

                $b_supply_id    = $carAry->b_supply_id;
                $b_supply       = $carAry->supply;
                $e_project_id   = $carAry->e_project_id;

                //工程案件->工程案件 允許進入廠區
                //list($e_project_id,$project) = view_project_factory::getProjectExist($b_supply_id,$b_factory_id);

                $name               = $carAry->car_no;
                $bc_type            = 999;//承攬商車輛
                $b_rfid_id          = $carAry->b_car_id;
                $rfid_code          = $carAry->car_no;
                $bid                = $carAry->b_car_id;
                $jobkindName        = b_car::getType($bid);
                $jobkind            = 4;
                $door_result        = 'Y'; //可以進入
                $door_result_name   = \Lang::get('sys_base.base_10716');
                $door_result_kind   = 1;

            } elseif(in_array($mode,[3,4,9]) && strlen($bcid) >= 5) {
                //手動開門進來的車輛
                $isExist        = 1; //白名單判斷
                $isSupply       = 1; //承攬商判斷
                $isUser         = 0; //人員車輛判斷

                $b_supply_id        = -1;
                $b_supply           = \Lang::get('sys_base.base_30127').(($mode != 9)? \Lang::get('sys_base.base_30128') : '');
                $e_project_id       = 0;
                $name               = $bcid;
                $bc_type            = 999;//承攬商車輛
                $b_rfid_id          = 0;
                $rfid_code          = $bcid;
                $bid                = 0;
                $jobkindName        = \Lang::get('sys_base.base_30129');
                $jobkind            = 4;
                $door_result        = 'Y'; //可以進入
                $door_result_name   = \Lang::get('sys_base.base_10716');
                $door_result_kind   = 1;
            }
        }

        //2-3-1. 不存在白名單[不紀錄]
        if($isUser && !$isExist)
        {
            //2-3-2 訪客證
            $isGustAry     = DB::table('view_guest_rent')->where('rfid_code',$bcid)->first();
            //dd($isGustAry);
            if(isset($isGustAry->id))
            {
                $isSupply           = 2; //訪客
                $isUser             = 2; //訪客
                $e_project_id       = -1;
                $project            = $isGustAry->rfid_name;
                $b_supply_id        = -1;
                $b_supply           = !is_null($isGustAry->guest_comp)? $isGustAry->guest_comp : '訪客公司';
                $name               = !is_null($isGustAry->guest_name)? $isGustAry->guest_name : '訪客';
                $visit_dept         = !is_null($isGustAry->visit_dept)? $isGustAry->visit_dept : '';
                $visit_emp          = !is_null($isGustAry->visit_emp)? $isGustAry->visit_emp : '';
                $b_rfid_id          = $isGustAry->b_rfid_id;
                $rfid_code          = $isGustAry->rfid_code;
                $violation_memo     = '訪客：'.$b_supply.'，'.$name.'，拜訪單位：'.$visit_dept.' '.$visit_emp;
                $visit_memo         = $visit_dept.' '.$visit_emp;
                $bc_type            = 4;
                $bid                = $isGustAry->id;
                $project_door_rule  = 4;
                $cpc_tag            = 'E';
                $jobkind            = 4;
                $isExist            = 1;
                $door_result        = 'Y'; //可以進入
                $door_result_name   = \Lang::get('sys_base.base_10716');
                $door_result_kind   = 1;
            } else {
                $isGustAry2         = DB::table('b_rfid')->where('rfid_type',4)->where('rfid_code',$bcid)->first();
                if(isset($isGustAry2->id))
                {

                    $resultAry = [];
                    $resultAry[] = ['',''];
                    $resultAry[] = [Lang::get('sys_base.base_30132',['name'=>$isGustAry2->name])];
                    return [-1,'N',$door_result_kind,$door_result_name,'',$isOnline,0,$resultAry];
                } else {
                    //3-2-3 黑名單
                    $isBlackListAry     = DB::table('view_door_blacklist')->where('b_cust_id',$bcid)->first();
                    if(isset($isBlackListAry->error) )
                    {

                        $isSupply           = ($isBlackListAry->bc_type == 3)? 1 : 0;
                        $e_project_id       = $isBlackListAry->e_project_id;
                        $project            = $isBlackListAry->project_name;
                        $b_supply_id        = $isBlackListAry->unit_id;
                        $b_supply           = $isBlackListAry->unit;
                        $name               = $isBlackListAry->name;
                        $b_rfid_id          = $isBlackListAry->b_rfid_id;
                        $rfid_code          = $isBlackListAry->rfid_code;
                        $bc_type            = $isBlackListAry->bc_type;
                        $bid                = $isBlackListAry->b_cust_id;
                        $project_door_rule  = $isBlackListAry->door_check_rule;
                        $cpc_tag            = $isBlackListAry->cpc_tag;
                        $jobkind            = $isBlackListAry->job_kind;
                        if($jobkind != 4 && $cpc_tag == 'A') $jobkind = 2;
                        if($jobkind != 4 && $cpc_tag == 'B') $jobkind = 3;
                        if($jobkind != 4 && $cpc_tag == 'E') $jobkind = 4;
                        if(isset($jobAry[$jobkind]))$jobkindName = $jobAry[$jobkind];

                        $error    = isset($isBlackListAry->error)? $isBlackListAry->error : 0;
                        $errorAry = [0=>'base_30126',1=>'base_30123',2=>'base_30124',3=>'base_30125'];
                        $errorAry2 = [0=>'14',1=>'15',2=>'16',3=>'17'];
                        $errorStr = isset($errorAry[$error])? $errorAry[$error] : $errorAry[0];

                        $errCode            = isset($errorAry2[$error])? $errorAry2[$error] : $errorAry2[0];
                        $door_result        = 'N'; //不可進入
                        $door_result_name   = \Lang::get('sys_base.base_10717');
                        $door_result_kind   = 2;
                        $violation_memo     = \Lang::get('sys_base.'.$errorStr);
                        $isExist            = -2;//黑名單人員

                    } else {
                        //此人車不存在白名單，不得進入
                        $violation_memo   = Lang::get('sys_base.base_30150');
                        $door_result_tag  = Lang::get('sys_base.base_30151');
                        $strcode          = ($isUser)? 'base_30102' : 'base_30106';

                        $name           = Lang::get('sys_base.base_30165');
                        $door_memo1     = Lang::get('sys_base.base_30100',['door_result'=>$door_result_tag,'door_stamp'=>$time]);
                        $door_memo2     = Lang::get('sys_base.'.$strcode ,['name'=>$name,'supply'=>$b_supply,'project'=>$project]);
                        $door_memo3     = Lang::get('sys_base.base_30103',['door_type'=>$door_result_tag]);
                        $door_memo4     = Lang::get('sys_base.base_30104',['name'=>'','door_type'=>'','memo'=>$violation_memo]);
                        $door_memo      = $door_memo2.$door_memo3.$door_memo1.$door_memo4;

                        //2019-11-08 配合昱俊
                        $door_violation2    = $violation_memo ? Lang::get('sys_base.base_30117',['name'=>$name,'door_type'=>$door_result_tag,'memo'=>$violation_memo]) : '';

                        $resultAry = [];
                        $resultAry[] = ['',''];
                        $resultAry[] = [Lang::get('sys_base.base_30116'),$door_violation2];

                        return [-1,'N',$door_result_kind,$door_result_name,$door_memo,$isOnline,0,$resultAry];
                    }
                }
            }
            unset($isGustAry,$isGustAry2,$isBlackListAry);
        }

        //2-3-3. 離線狀態下，資料判斷檢核: 重複資料不在寫入[不紀錄]
        if($isExist > 0)
        {
            if($isOnline == 'N' || $isOnline2 == 'N') {
                list($logid,$door_result,$door_memo,$door_type,$door_data) = LogLib::getInOutLog($isUser,$bid,$time,$b_factory_id,$b_factory_d_id,'','Y');

                if($logid)
                {
                    return [$logid,$door_result,$door_result_kind,$door_result_name,$door_memo,$isOnline,$door_type,$door_data];
                } elseif($isExist > 0) {
                    $door_result        = 'Y'; //可以進入
                    $door_result_name   = \Lang::get('sys_base.base_10716');
                    $door_result_kind   = 1;
                    $door_memo          = '';
                    $door_type          = 0;
                }
            }

            //2-3-4. 連續刷卡不得重複
            [$last_log_id,$last_door_type,$last_result,$last_memo] = LogLib::getRepeatDoor($b_factory_id,$b_factory_d_id,$isUser,$bid,$door_max_repeat_stamp);
            if($last_log_id)
            {
                $strcode            = ($isUser)? 'base_30102' : 'base_30106';
                $last_door_type_name= isset($doorTypeAry[$last_door_type])? $doorTypeAry[$last_door_type] : Lang::get('sys_base.base_30164');
                $door_result_tag    = isset($doorResultAry[$last_result])? $doorResultAry[$last_result] : Lang::get('sys_base.base_30166');
                if($last_result == 'N')
                {
                    $violation_memo     = Lang::get('sys_base.base_30168',['name1'=>$door_max_repeat_limit,'name2'=>$last_memo]);
                } else {
                    $violation_memo     = Lang::get('sys_base.base_30167',['name1'=>$last_door_type_name,'name2'=>$name,'name3'=>$name,'name4'=>$time]);
                }

                $door_memo1       = Lang::get('sys_base.base_30100',['door_result'=>$door_result_tag,'door_stamp'=>$time]);
                $door_memo2       = Lang::get('sys_base.'.$strcode ,['name'=>$name,'supply'=>$b_supply,'project'=>$project]);
                $door_memo3       = Lang::get('sys_base.base_30103',['door_type'=>$door_result_tag]);
                $door_memo4       = Lang::get('sys_base.base_30104',['name'=>$name,'door_type'=>$last_door_type_name,'memo'=>$violation_memo]);
                $door_memo        = $door_memo2.$door_memo3.$door_memo1.$door_memo4;

                $logid              = $last_log_id;
                //2020-01-30 重複刷卡判斷為失敗（烏才版本是成功）　
                $door_result        = 'N';//$last_result;
                $door_result_name   = \Lang::get('sys_base.base_30131');
                $door_result_kind   = 3;
                $isOnline           = 'Y';
                $door_type          = $last_door_type;

                //2019-11-08 配合昱俊 本地端刷卡無法過濾連續刷卡問題
                //連續刷卡時間不得再 60秒內
                $door_violation2    = $violation_memo ? Lang::get('sys_base.base_30117',['name'=>$name,'door_type'=>$door_result_tag,'memo'=>$violation_memo]) : '';

                $door_data = [];
                $door_data[] = ['',''];
                if($last_result == 'N')
                {
                    $door_data[] = [Lang::get('sys_base.base_30116'),$door_violation2];
                } else {
                    $door_data[] = [Lang::get('sys_base.base_30111'),$violation_memo];
                }

                return [$logid,$door_result,$door_result_kind,$door_result_name,$door_memo,$isOnline,$door_type,$door_data];
            }
        }

        //===========================================================//
        //3. 判斷 進出 ＆ 違規查核 ＆ 工安規則檢核
        //===========================================================//
        //僅紀錄 有在白名單內的人員與車輛
        if($isExist != 0)
        {
            //3-1. 先判斷 進出模式
            if(in_array($mode,[1,2,3,4,9]))
            {
                //3-1-1. 如果讀卡機 回傳 進出狀態「進廠」＆「離廠」
                $door_type = $mode;
            } else {
                $chkInOut = 0;
                if($isOnline == 'N')
                {
                    $last_log_id = LogLib::checkLastLog($isUser,$bid,$time,$b_factory_id,$b_factory_d_id);
                    if($last_log_id)
                    {
                        $chkInOut = 1;
                    }
                }
                if($isOnline == 'Y')
                {
                    $chkInOut = 1;
                }
                if($chkInOut)
                {
                    //3-1-2. 自動判斷[人員＆車輛]
                    list($lastDoorId,$lastDoorName,$lastDoorType) = LogLib::getLastInOutTypeLog($b_factory_id,$bid,$isUser);
                    $door_type    = ($lastDoorType == 1)? 2 : 1;
                }
            }
//            dd([$isExist,$door_result,$mode,$door_type,$isOnline,$lastDoorType]);

            //3-2-1. 禁止進入該區「未授權」
            if($isExist == -1)
            {
                $errCode        = 1; //未授權在廠
                $violation_memo = (!$e_project_id)? Lang::get('sys_base.base_30156') : Lang::get('sys_base.base_30171',['name1'=>$doorName]); //違規事由：未授權該廠區進出資格
                //在廠：拒絕
                if($door_type == 1 && $isOnline == 'Y')
                {
                    $door_result        = 'N'; //不可進入
                    $door_result_name   = \Lang::get('sys_base.base_10717');
                    $door_result_kind   = 2;
                }
            }elseif($isExist == -3)
            {
                $errCode        = 18; //未授權在廠
                $violation_memo = Lang::get('sys_base.base_30176'); //違規事由：請先申請施工人員身分(勞健保)
                //在廠：拒絕
                if($door_type == 1 && $isOnline == 'Y')
                {
                    $door_result        = 'N'; //不可進入
                    $door_result_name   = \Lang::get('sys_base.base_10717');
                    $door_result_kind   = 2;
                }
            } elseif($isExist > 0) {
                //3-2-3. [人]進入模式：違規查核
                if( $door_type == 1 && $isOnline == 'Y' && $door_result == 'Y')
                {
                    //人員身份
                    if($isUser == 1)
                    {
                        //3-2-2 違規紀錄，則拒絕
                        if($vid = e_violation_contractor::isMemberExist($bid))
                        {
                            $errCode            = 2;//違規
                            $door_result        = 'N'; //不可進入
                            $door_result_name   = \Lang::get('sys_base.base_10740');
                            $door_result_kind   = 4;
                            $violation_memo     = e_violation_contractor::getName($vid); //違規事由
                        }
                    }
                }
                //dd([$bcid,$b_factory_id,$personAry,$door_type,$door_result,$violation_memo,$jobkind]);

                //3-3. 工安規則檢核：允許進去 - 2019/05/02 僅限人員檢核
                //2019/08/07 新增 特殊成員 不檢查規則<$jobkind !== =  4>
                //2019/06 新增 工作許可證之工安＆工負判斷
                // 項次 191 (2021/6/18) jobkind = 4 特殊進出人員進廠時不需檢查，離場時檢查需同一門，但不需檢查場內是否仍有施工人員
                if ($isSupply && $isUser && $isOnline == 'Y' && $door_result == 'Y' && !($jobkind == 4 && $door_type == 1))
                {
                    /*
                     * 門禁許可檢查規則
                     * 1: 不檢查 工安＆工負
                     * 2: 檢查工程案件的 工安＆工負
                     * 3: 檢查 工作許可證的 工安＆工負
                     */
                    //$def_door_rule_kind = sys_param::getParam('DOOR_RULE_KIND','1');
                    //該門口的進出規則預設依據為[1]白名單[3]工程案件ｏｒ工單
                    $def_door_rule_kind = b_factory_d::getDoorType($b_factory_d_id);
                    //2021-02-20
                    if($def_door_rule_kind > 1)
                    {
                        //依據工程案件之門禁規則[2]工程案件[3]工單　之工安＆工負規則
                        $door_rule_kind = $project_door_rule;
                    }
                    if(!$door_rule_kind) $door_rule_kind = $def_door_rule_kind;
//                    dd($door_rule_kind,$project_door_rule,$def_door_rule_kind);
                    //[3]規則：依據工作許可證的工安＆工負
                    if($door_rule_kind == '3')
                    {
                        //取得當日工作許可證相關資訊
                        list($work_id,$work_aproc,$work_danger,$identity,$isRoot,$isIn,$inErrCode,$InerrCodeNo,$isAdd,$isLevel) = view_wp_work::getTodayWork($e_project_id,$b_factory_id,$b_factory_d_id,$bid,$door_type);
//                    dd($work_id,$identity,$isRoot,$isIn,$inErrCode,$InerrCodeNo,$isAdd,$isLevel,[$e_project_id,$b_factory_id,$b_factory_d_id,$bid,$door_type]);
                        //不得進廠＆離廠 原因
                        if(!$isIn) {
                            $door_result        = 'N';
                            $door_result_name   = \Lang::get('sys_base.base_10717');
                            $door_result_kind   = 2;
                            $errCode            = $InerrCodeNo;
                            $violation_memo     = Lang::get('sys_base.'.$inErrCode);
                        }

                        //取得當日工作許可證-執行單ＩＤ
                        if($work_id)
                        {
                            //門別限制 2021-01-15
                            list($work_door_id,$work_door_name) = wp_work::getDoor($work_id);
                            //身份：工地負責人(工單)
                            if($identity == $identity_A) {
                                $jobkind     = 2;
                                $jobkindName = Lang::get('sys_base.base_40231');
                            }
                            //身份：安衛人員(工單)
                            if($identity == $identity_B) {
                                $jobkind     = 3;
                                $jobkindName = Lang::get('sys_base.base_40232');
                            }
                            // 取得 該工程案件 所屬 工地負責人＆工安人員
                            $door_rule   = e_project::getDoorCheckRule($e_project_id);
                            $jobUserAry  = wp_work_worker::getSelect($work_id,1,0);
                            $safeUserAry = wp_work_worker::getSelect($work_id,2,0);

                            if($b_factory_d_id != $work_door_id && in_array($jobkind,[1,3]))
                            {
                                //如果與工作許可証 指定的門別不同，禁止入廠
                                $door_result        = 'N';
                                $door_result_name   = \Lang::get('sys_base.base_10717');
                                $door_result_kind   = 2;
                                $errCode            = 12;//非限定門進出
                                $violation_memo = Lang::get('sys_base.base_30169',['name1'=>$work_door_name,'name2'=>$name]);
                            } else {
                                //進場＋工作人員
                                if($door_type == 1 && !in_array($identity,[$identity_A,$identity_B]))
                                {
                                    if(!in_array($work_aproc,['A','W','P','K']))
                                    {
                                        $door_rule = 2; //安衛人員優先進場
                                    }
//                            dd($door_rule,$work_aproc);
                                }
                                //工地負責人 離場<施工中＋>
                                if($door_type == 2 && $jobkind == 2 && in_array($work_aproc,['R','O','F']))
                                {
                                    $inAry      = wp_work_worker::getLockMenSelect($work_id,'',0,'',0,[],1);
                                    $workerAry  = wp_work_worker::getLockMenSelect($work_id,'',0,'',1,[9]); //不包含管理人員

                                    //進場有人數兩人以上 ＋ 不是Ａ級 ＋ 也不在工作人員內
                                    if(count($inAry) > 2 && $work_danger != 'A' && !in_array($bid,$workerAry))
                                    {
                                        //解除他的管理者身份
                                        $isRoot = 0;
                                        //不限制他進出
                                        $door_rule_kind = 1;
                                        //釋放
                                        $tmp = [];
                                        $tmp['wp_work_id']  = 0;
                                        rept_doorinout_t::where('wp_work_id',$work_id)->where('door_date',date('Y-m-d'))->where('b_cust_id',$bid)->update($tmp);

                                        //dd($work_aproc,$inAry,$workerAry);
                                    } else {
                                        if(count($inAry) == 2)
                                        {
                                            //$workRootErr = 11;//人數只有兩人
                                        }
                                        if($work_danger == 'A')
                                        {
                                            $workRootErr = 10;//Ａ級作業
                                        }
                                        if(in_array($bid,$workerAry))
                                        {
                                            $workRootErr = 9;//擔任其他要職
                                        }

                                    }
                                }
                            }

//                        dd($door_rule,$work_aproc,$isRoot);
                        } else {
                            //工安＆工負　可以自由進廠　2021/01/18
                            if($door_type == 1)
                            {
                                if(in_array($jobkind,[2,3]))
                                {
                                    $door_result        = 'Y';
                                    $door_result_name   = \Lang::get('sys_base.base_10716');
                                    $door_result_kind   = 1;
                                    $door_rule_kind     = 1;
                                    $errCode            = 0 ;
                                    $violation_memo     = '';
                                }
                            }
                            //如果是離場，且找不到 正在進行＆收工的工作許可證，不擋該人員
                            if($door_type == 2) {
                                $door_result        = 'Y';
                                $door_result_name   = \Lang::get('sys_base.base_10716');
                                $door_rule_kind     = 1;
                                $door_result_kind   = 1;
                                $errCode            = 0 ;
                                $violation_memo     = '';
                            }
                        }
//                    dd($door_type,$door_result,$door_rule_kind);
                    } elseif($door_rule_kind == '2') {
                        //[2]規則：依據工程案件的工安＆工負
                        //本身是否為 工地負責人 ＆ 工安人員
                        $isRoot = e_project_s::isAdUser($e_project_id,$bid);
                        // 取得 該工程案件 所屬 工地負責人＆工安人員 + 特殊人員
                        list($door_rule,$jobUserAry,$safeUserAry,$specialUserAry)  = e_project::getDoorRule($e_project_id);

                    } else {
                        //[1]:不檢查工安＆工負
                    }

                    //if( $bid== 2000000578 ) dd($door_rule_kind,$door_type,$isRoot,$door_rule);
                    //需要檢查工安＆工負進場順序規則 ＆離場順序規則
                    if($door_result == 'Y' && $door_rule_kind > 1)
                    {
                        $isJobCheck  = 0;
                        $isSafeCheck = 0;
                        $JobCheckCnt = count($jobUserAry);
                        $SafeCheckCnt= count($safeUserAry);
                        //工地負責人
                        if($JobCheckCnt)
                        {
                            $jobUserInAry = view_log_door_today::getUserInArray($jobUserAry, $e_project_id, 0, 0, $now);
                            $isJobCheck = count($jobUserInAry);
                            // $isJobCheck = rept_doorinout_t::getExistAmt($b_factory_id,0,$door_date,$jobUserAry,1);
                        }
                        //安全人員(必須要同一個門進出　２０２０－０１－２１)
                        if($SafeCheckCnt)
                        {
                            $safeUserInAry = view_log_door_today::getUserInArray($safeUserAry, $e_project_id, $b_factory_id, $b_factory_d_id, $now);
                            $isSafeCheck = count($safeUserInAry);
                            // $isSafeCheck = rept_doorinout_t::getExistAmt($b_factory_id,$b_factory_d_id,$door_date,$safeUserAry,1);
                        }
//                    dd(['door_type'=>$door_type,'rule'=>$door_rule,'rule_kind'=>$door_rule_kind,
//                        'root1'=>$jobUserAry,'root2'=>$safeUserAry,'isJob'=>$isJobCheck,'isSafe'=>$isSafeCheck]);
                        //if( $bid== 2000000578 ) dd($door_rule_kind,$door_type,$isRoot,$door_rule,$isJobCheck,$jobUserAry,$isSafeCheck);

                        //進場時，確認是否需要補人
                        if($door_type == 1){
                            $work_id2 = view_wp_work::getTodayWork2($e_project_id, $b_factory_id, $bid, true);
                            if ($work_id2) {
                                $work_id = $work_id2;
                                $isAdd = 1;
                            } else {
                                // 判斷是否有綁定在工單中
                                if(empty($work_id)) $work_id = view_wp_work::getTodayWork2($e_project_id, $b_factory_id, $bid, false, true);
                            }
                        }

                        //在廠
                        if($door_type == 1 && !$isRoot)
                        {
                            //規則Ｂ：工安在場即可
                            if($door_rule == 2)
                            {
//                            //工安在場即可
//                            if(!$isSafeCheck)
//                            {
//                                $errCode        = 4;//工安優先在廠
//                                $door_result    = 'N';
//                                $violation_memo = Lang::get('sys_base.base_30163');
//                            }
                                //規則Ａ：皆需在場，即可
                                if(!$isJobCheck || !$isSafeCheck)
                                {
                                    $errCode            = 4;//工負＆工安優先在廠
                                    $door_result        = 'N';
                                    $door_result_name   = \Lang::get('sys_base.base_10717');
                                    $door_result_kind   = 2;
                                    $err_msg            = '';
                                    if(!$isJobCheck && !$isSafeCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30172');//（工安工負皆未進廠）
                                    } elseif(!$isJobCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30173');//（工負未進廠）
                                    }elseif(!$isSafeCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30174');//（工安未進廠）
                                    }

                                    $violation_memo = Lang::get('sys_base.base_30152').$err_msg;
                                }
                            }
                            //規則C：擇一在場，即可
                            elseif($door_rule == 1)
                            {
                                if(!$isJobCheck && !$isSafeCheck)
                                {
                                    $errCode            = 4;//工負＆工安優先在廠
                                    $door_result        = 'N';
                                    $door_result_name   = \Lang::get('sys_base.base_10717');
                                    $door_result_kind   = 2;
                                    $violation_memo     = Lang::get('sys_base.base_30153');
                                }

                            } else {
                                //規則Ａ：皆需在場，即可
                                if(!$isJobCheck || !$isSafeCheck)
                                {
                                    $errCode            = 4;//工負＆工安優先在廠
                                    $door_result        = 'N';
                                    $door_result_name   = \Lang::get('sys_base.base_10717');
                                    $door_result_kind   = 2;
                                    $err_msg            = '';
                                    if(!$isJobCheck && !$isSafeCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30172');//（工安工負皆未進廠）
                                    } elseif(!$isJobCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30173');//（工負未進廠）
                                    }elseif(!$isSafeCheck)
                                    {
                                        $err_msg = Lang::get('sys_base.base_30174');//（工安未進廠）
                                    }

                                    $violation_memo = Lang::get('sys_base.base_30152').$err_msg;
                                }
                            }
                        }
                        //退場
                        elseif($door_type == 2)
                        {
                            if($door_rule == 2 && in_array($jobkind,[1,3,4]))
                            {
                                if($lastDoorId != $b_factory_d_id)
                                {
                                    $door_result        = 'N';
                                    $door_result_name   = \Lang::get('sys_base.base_10717');
                                    $door_result_kind   = 2;
                                    $errCode            = 12;//非限定門
                                    $violation_memo     = Lang::get('sys_base.base_30175',['name1'=>$lastDoorName]);
                                }
                            }
                            if($isRoot && $door_result == 'Y')
                            {
                                $adUserAry = array_merge($jobUserAry , $safeUserAry);
                                if(count($specialUserAry))
                                {
                                    $adUserAry = array_merge($adUserAry, $specialUserAry);
                                }

                                //dd([$jobkind,$work_aproc,$isJobCheck,$isSafeCheck,$door_rule,$jobUserAry,$safeUserAry,$adUserAry,rept_doorinout_t::isExist($b_factory_id,$door_date,0,1,$adUserAry,$work_id)]);

                                //如果階段：申請收工階段，且身份為 安衛人員 2019-10-17
                                if($jobkind == 3 && in_array($work_aproc,['O']) && $isSafeCheck < 2)
                                {
                                    $door_result        = 'N';
                                    $door_result_name   = \Lang::get('sys_base.base_10717');
                                    $door_result_kind   = 2;
                                    $errCode            = 8;//申請收工中，安衛人員不得離廠
                                    $violation_memo     = Lang::get('sys_base.base_30162');
                                }
                                //規則Ａ＋Ｂ＋Ｃ：一般身份皆離開，即可 (如果有同一張工作許可證的人員在)
                                elseif (count($adUserAry))
                                {
                                    if($work_id)
                                    {
                                        $workerArr     = wp_work_worker::getSelect($work_id,0,0,0,'',[1,2]);
                                        $workerInArr = view_log_door_today::getUserInArray($workerArr, $e_project_id, $b_factory_id, $b_factory_d_id, $now);
                                        // $isWorkerExist = rept_doorinout_t::isExist($b_factory_id,0,$door_date,0,1,$adUserAry,$work_id,$e_project_id);
                                    } else {
                                        $tagCWorkerArr = e_project_s::getJobUser($e_project_id,'C');
                                        $tagDWorkerArr = e_project_s::getJobUser($e_project_id,'D');
                                        $workerInArr = view_log_door_today::getUserInArray(array_merge($tagCWorkerArr,$tagDWorkerArr), $e_project_id, $b_factory_id, $b_factory_d_id, $now);
                                        // $isWorkerExist = rept_doorinout_t::isExist($b_factory_id,$b_factory_d_id,$door_date,0,1,$adUserAry,NULL,$e_project_id);
                                    }
                                    $isWorkerExist = count($workerInArr);
                                    // Log::info('b_cust_id = '.$bid.' &jobkind = '.$jobkind.' &door_rule = '.$door_rule.' &isWorkerExist = '.$isWorkerExist.' &isSafeCheck = '.$isSafeCheck.'&door_stamp = '.$time);
                                    if($isWorkerExist)
                                    {
                                        $isCanLevel = 1;
                                        //工負
                                        if($jobkind == 2 && $isJobCheck < 2 && in_array($work_aproc,['P','K']))
                                        {
                                            $isCanLevel = 0;
                                        }
                                        //工安
                                        if($jobkind == 3 && $isSafeCheck < 2 )
                                        {
                                            $isCanLevel = 0;
                                        }
//                            dd(['rept_id'=>$isWorkerExist,'job'=>$jobkind,'jobCnt'=>$JobCheckCnt,'hasJob'=>$isJobCheck,'safeCnt'=>$SafeCheckCnt,'hassSafe'=>$isSafeCheck,'hasWorker'=>$isWorkerExist,'CanLeve'=>$isCanLevel]);

                                        if(!$isCanLevel)
                                        {
                                            $errCode            = 5;//工負＆工安最後離場
                                            $door_result        = 'N';
                                            $door_result_name   = \Lang::get('sys_base.base_10717');
                                            $door_result_kind   = 2;
                                            $violation_memo     = Lang::get('sys_base.base_30154');
                                            if($workRootErr)
                                            {
                                                $errCode    = $workRootErr;
                                                $violation_memo = isset($doorErrCodrAry[$workRootErr])? $doorErrCodrAry[$workRootErr] : $violation_memo;
                                            }
                                        }
                                    }
                                }
                            }

//                        dd([$jobkind,$work_aproc,$isJobCheck,$isSafeCheck,$isWorkerExist,$door_rule,$jobUserAry,$safeUserAry,$adUserAry,rept_doorinout_t::isExist($b_factory_id,$door_date,0,1,$adUserAry,$work_id)]);

                        }
                    }
                }

                elseif($isSupply && $isUser && $isOnline == 'Y' && $door_result == 'Y' && $jobkind ==  4)
                {
                    if($isUser == 1)
                    {
                        //特殊人員參加 工程案件，判斷是否釋放
                        $work_id = rept_doorinout_t::getWorkId($b_factory_id,$today,$bid);
                        $aproc   = wp_work::getAproc($work_id);
                        if($door_type == 2 && in_array($aproc,['W','F','K','C']))
                        {
                            $isLevel = 1;
                        }
                    }
                }
            }
//            dd($isUser , $door_type , $work_id , $isLevel, $door_result,$violation_memo);
            //===========================================================//
            //４. 紀錄本次進出紀錄
            //===========================================================//
            //4-1. 進出紀錄 陣列
            //TODO 2021-04-13 回來改寫
            $retAry[] = $bid;               //ID
            $retAry[] = $name;              //人員姓名＆車牌
            $retAry[] = $bc_type;           //類別
            $retAry[] = $b_supply_id;       //承攬商
            $retAry[] = $b_supply;          //承攬商名稱
            $retAry[] = $b_rfid_id;         //RFID ID
            $retAry[] = $rfid_code;         //RFID 內碼
            $retAry[] = $door_type;         //進出模式
            $retAry[] = $time;              //進出時間
            $retAry[] = $b_factory_id;
            $retAry[] = $b_factory_d_id;
            $retAry[] = $e_project_id;
            $retAry[] = $door_result;
            $retAry[] = $violation_memo;
            $retAry[] = $jobkindName;
            $retAry[] = $work_id;           //當日工作許可證
//            dd([$retAry,$errCode,$isOnline,$isOver,$isAdd,($isUser && $door_type == 1 && $work_id && $isAdd)]);
//            dd($work_id,$bid,$b_factory_id,$b_factory_d_id,$bid);
            //4-2. 寫入Log
            $logid = LogLib::putInOutLog($retAry,$img,$errCode,$isOnline,$isOver);

            // $retAry['log_id'] = $logid;
            // $retAry['worker_in'] = $workerInArr;
            // $retAry['job_in'] = $jobUserInAry;
            // $retAry['safe_in'] = $safeUserInAry;
            // Log::info(json_encode($retAry));

            //4-3. 更新每日進出報表
            if($logid && $door_result == 'Y')
            {
                if($isUser)
                {
                    //人員每日 進出報表
                    $INS = $this->createDoorMenInOutToday($retAry,$logid,$work_id);

                    if($isUser == 1)
                    {
                        //4-4. 工作許可證紀錄<離場，且有生效的工作許可證>
                        if($door_type == 2 && $work_id)
                        {
                            $this->setWrokPermitWorkerMenOut($work_id,$bid);
                        }

                        //4-5. 啟動 工作許可證 補人之成員
                        if($door_type == 1 && $work_id && $isAdd)
                        {
                            $this->updateWorkerMenReady($work_id,$bid,$b_factory_id,$b_factory_d_id,$bid);
                        }

                        //4-6. 釋放
                        //2020-01-30 因為要綁定各門別工單，故離場即釋放
                        if($door_type == 2 && $work_id && $isLevel)//&& $isLevel
                        {
                            $this->freedWorkPermitWorkerMen($bid);
                        }

                        //4-7. 工地負責人離場通知
                        if($door_type == 2 && $work_id && $identity == $identity_A)
                        {
                            $name = User::getName($bid);
                            $this->pushToSupplyRootLeave($work_id,$name);
                        }
                    }
                    //訪客記錄回寫
                    else
                    {
                        LogLib::setGuestRecord($bid,$door_type);
                    }

                } else {
                    //車輛每日 進出報表
                    $INS = $this->createDoorCarInOutToday($retAry,$logid);
                }
            }
            //===========================================================//
            //5. 組合回饋的訊息
            //===========================================================//
            //5-1. 參數

            //5-2-1. 進出模式文字
            $door_type_name  = isset($doorTypeAry[$door_type])? $doorTypeAry[$door_type] : Lang::get('sys_base.base_30164');
            //5-2-2. 進出結果文字
            $door_result_tag = isset($doorResultAry[$door_result])? $doorResultAry[$door_result] : Lang::get('sys_base.base_30151');
            //5-2-3. 人/車
            $strcode = ($isUser)? 'base_30102' : 'base_30106';

            //5-3. 組合文字
            $door_violation     = $violation_memo ? Lang::get('sys_base.base_30104',['name'=>$name,'door_type'=>$door_type_name,'memo'=>$violation_memo]) : '';
            $door_violation2    = $violation_memo ? Lang::get('sys_base.base_30117',['name'=>$name,'door_type'=>$door_type_name,'memo'=>$violation_memo]) : '';

            //2020-02-01
            $resultAry = [];
            $workRoot        = wp_work_worker::getWorkInfo($work_id,2);
            $permit_no       = isset($workRoot['no'])? $workRoot['no'] : '';
            $worker1         = isset($workRoot['worker1'])? $workRoot['worker1'] : '';
            $worker2         = isset($workRoot['worker2'])? $workRoot['worker2'] : '';
            if($door_result == 'N')
            {
                $resultAry[] = ['',''];
                $resultAry[] = [Lang::get('sys_base.base_30116'),$door_violation2];
                $resultAry[] = [Lang::get('sys_base.base_30120'),$permit_no];
                $resultAry[] = [Lang::get('sys_base.base_30121'),$worker1];
                $resultAry[] = [Lang::get('sys_base.base_30122'),$worker2];
            } else {
                $resultAry[] = [Lang::get('sys_base.base_30112'),$b_supply];
                $resultAry[] = [Lang::get('sys_base.base_30113'),$name];
                if($bc_type == 4)
                {
                    $resultAry[] = [Lang::get('sys_base.base_30133'),$project];
                    $resultAry[] = [Lang::get('sys_base.base_30134'),$visit_memo];
                } else {
                    $resultAry[] = [Lang::get('sys_base.base_30114'),$project];
                }

                $resultAry[] = [Lang::get('sys_base.base_30115'),$door_type_name];
                $resultAry[] = [Lang::get('sys_base.base_30110'),$time];
                //$resultAry[] = [Lang::get('sys_base.base_30111'),$door_result_tag];
                if($door_rule_kind == 3)
                {
                    $resultAry[] = [Lang::get('sys_base.base_30120'),$permit_no];
                    $resultAry[] = [Lang::get('sys_base.base_30121'),$worker1];
                    $resultAry[] = [Lang::get('sys_base.base_30122'),$worker2];
                }
            }
            $door_memo1     = Lang::get('sys_base.base_30100',['door_result'=>$door_result_tag,'door_stamp'=>$time]);
            $door_memo2     = Lang::get('sys_base.'.$strcode ,['name'=>$name,'supply'=>$b_supply,'project'=>$project]);
            $door_memo3     = Lang::get('sys_base.base_30103',['door_type'=>$door_type_name]);
            $door_memo4     = $door_violation;
            $door_memo      = $door_memo2.$door_memo3.$door_memo1.$door_memo4;

        }

        return [$logid,$door_result,$door_result_kind,$door_result_name,$door_memo,$isOnline,$door_type,$resultAry];
    }

    /**
     * 取得 白名單
     * @param $dataKind 1:全部 2:人 3:車
     * @param $type 1:卡號+人車 2:照片
     * @param int $img_at
     * @return bool
     */
    public function getWhiteList($dataKind = 1, $type = 1, $img_at = 0)
    {
        $no = 0;
        $whitelistReplyAry = $rfidcardReplyAry = $projectReplyAry = $headAry = [];
        $defaultMenImg     = public_path('images/door_men.jpg');
        $defaultCarImg     = public_path('images/door_car.jpg');
        //1. 承攬商白名單
        if(in_array($dataKind,[1,2]))
        {
            //訪客
            $guestAry = DB::table('b_rfid')->where('isClose','N')->where('rfid_type',4)->
            select('name','id','rfid_code')->get()->toArray();
            if(count($guestAry))
            {
                foreach ($guestAry as $key => $val)
                {
                    //人頭像 _ 加上 時輟判斷 是否異動
                    if($type == 2)
                    {
                        $img_data = '';
                        $headAry[$no]['name']       = $val->rfid_code;
                        $headAry[$no]['head']       = $img_data;
                        $headAry[$no]['at1']        = 0;
                        $headAry[$no]['at2']        = 0;
                        $headAry[$no]['card_kind']  = 'M';
                    } else {
                        //訪客
                        $whitelistReplyAry[$no]['id']      = $val->rfid_code;
                        $whitelistReplyAry[$no]['name']    = $val->name;
                        $whitelistReplyAry[$no]['bc_type'] = 1;
                        $whitelistReplyAry[$no]['rule']    = 'Y';
                        $whitelistReplyAry[$no]['memo']    = '';
                        $whitelistReplyAry[$no]['project_no']       = ''; // 案件編號
                        $whitelistReplyAry[$no]['job_kind']         = 4; // 工程身分 1: 施工人員 2: 工負 3: 工安 4: 特殊進出人員 5: 待補施工身分人員
                        $whitelistReplyAry[$no]['door_check_rule']   = 1; // 門禁規則 1: 不限制 2: 案件之工安 工負 3: 工作許可證之工安 工負
                        //RFID CARD
                        $rfidcardReplyAry[$no]['id']       = $val->rfid_code;
                        $rfidcardReplyAry[$no]['name']     = $val->rfid_code;
                        $rfidcardReplyAry[$no]['type']     = 1;
                        $rfidcardReplyAry[$no]['rule']     = 'Y';
                        $rfidcardReplyAry[$no]['memo']     = '';
                        $rfidcardReplyAry[$no]['project_no']       = '';
                        $rfidcardReplyAry[$no]['job_kind']         = 4;
                        $rfidcardReplyAry[$no]['door_check_rule']   = 1;
                    }
                    $no++;
                }
            }
            //黑名單
            $blacklistAry = DB::table('view_door_blacklist')->
            select('name','b_cust_id','bc_id','project_no','job_kind','door_check_rule','head_img_at','head_img','rfid_code','error')->get()->toArray();
            if(count($blacklistAry))
            {
                foreach ($blacklistAry as $key => $val)
                {
                    //人頭像 _ 加上 時輟判斷 是否異動
                    if($type == 2 )
                    {
                        if( ($val->head_img_at == 0 && $img_at == 0) || ($val->head_img_at > $img_at))
                        {
                            $img_data = '';
                            $img_path = ($val->head_img)? storage_path('app'.$val->head_img) : '';
                            if($img_path && file_exists($img_path))
                            {
                                $imgData = SHCSLib::tranImgToBase64Img($img_path,150);
                                $imgDataAry = explode(',',$imgData);

                                $img_data = isset($imgDataAry[1])? $imgDataAry[1] : '';
                            }
                            $headAry[$no]['name']       = $val->b_cust_id;
                            $headAry[$no]['head']       = $img_data;
                            $headAry[$no]['at1']        = $val->head_img_at;
                            $headAry[$no]['at2']        = $img_at;
                            $headAry[$no]['card_kind']  = 'M';
                        }
                    } else {
                        $error = isset($val->error)? $val->error : 0;
                        $errorAry = [0=>'base_30126',1=>'base_30123',2=>'base_30124',3=>'base_30125'];
                        $errorStr = isset($errorAry[$error])? $errorAry[$error] : $errorAry[0];
                        //黑名單
                        $whitelistReplyAry[$no]['id']      = $val->b_cust_id;
                        $whitelistReplyAry[$no]['name']    = $val->name;
                        $whitelistReplyAry[$no]['bc_type'] = ($error == 1)? 1 : 0;
                        $whitelistReplyAry[$no]['rule']    = ($error == 1)? 'Y' : 'N';
                        $whitelistReplyAry[$no]['memo']    = ($error == 1)? '' : \Lang::get('sys_base.'.$errorStr);
                        $whitelistReplyAry[$no]['project_no']       = $val->project_no;
                        $whitelistReplyAry[$no]['job_kind']         = $val->job_kind;
                        $whitelistReplyAry[$no]['door_check_rule']  = $val->door_check_rule;
                        //RFID CARD
                        $rfidcardReplyAry[$no]['id']       = $val->rfid_code;
                        $rfidcardReplyAry[$no]['name']     = $val->b_cust_id;
                        $rfidcardReplyAry[$no]['type']     = ($error == 1)? 1 : 0;
                        $rfidcardReplyAry[$no]['rule']     = ($error == 1)? 'Y' : 'N';
                        $rfidcardReplyAry[$no]['memo']     = ($error == 1)? '' : \Lang::get('sys_base.'.$errorStr);
                        $rfidcardReplyAry[$no]['project_no']       = $val->project_no;
                        $rfidcardReplyAry[$no]['job_kind']         = $val->job_kind;
                        $rfidcardReplyAry[$no]['door_check_rule']  = $val->door_check_rule;
                    }
                    $no++;
                }
            }
            //白名單
            $whitelistAry = DB::table('view_door_supply_whitelist_pass')->
            select('name','b_cust_id','bc_id','project_no','job_kind','door_check_rule','head_img_at','head_img','rfid_code')->get()->toArray();
            if(count($whitelistAry))
            {
                foreach ($whitelistAry as $key => $val)
                {
                    //人頭像 _ 加上 時輟判斷 是否異動
                    if($type == 2 )
                    {
                        if( ($val->head_img_at == 0 && $img_at == 0) || ($val->head_img_at > $img_at))
                        {
                            $img_data = '';
                            $img_path = ($val->head_img)? storage_path('app'.$val->head_img) : '';
                            if($img_path && file_exists($img_path))
                            {
                                $imgData = SHCSLib::tranImgToBase64Img($img_path,150);
                                $imgDataAry = explode(',',$imgData);

                                $img_data = isset($imgDataAry[1])? $imgDataAry[1] : '';
                            }
                            $headAry[$no]['name']       = $val->b_cust_id;
                            $headAry[$no]['head']       = $img_data;
                            $headAry[$no]['at1']        = $val->head_img_at;
                            $headAry[$no]['at2']        = $img_at;
                            $headAry[$no]['card_kind']  = 'M';
                        }
                    } else {
                        //白名單
                        $whitelistReplyAry[$no]['id']      = $val->b_cust_id;
                        $whitelistReplyAry[$no]['name']    = $val->name;
                        $whitelistReplyAry[$no]['bc_type'] = 1;
                        $whitelistReplyAry[$no]['rule']     = 'Y';
                        $whitelistReplyAry[$no]['memo']     = '';
                        $whitelistReplyAry[$no]['project_no']       = $val->project_no;
                        $whitelistReplyAry[$no]['job_kind']         = $val->job_kind;
                        $whitelistReplyAry[$no]['door_check_rule']  = $val->door_check_rule;
                        //RFID CARD
                        $rfidcardReplyAry[$no]['id']       = $val->rfid_code;
                        $rfidcardReplyAry[$no]['name']     = $val->b_cust_id;
                        $rfidcardReplyAry[$no]['type']     = 1; //人員 （2019-04-04配合昱俊本地端設定）
                        $rfidcardReplyAry[$no]['rule']     = 'Y';
                        $rfidcardReplyAry[$no]['memo']     = '';
                        $rfidcardReplyAry[$no]['project_no']       = $val->project_no;
                        $rfidcardReplyAry[$no]['job_kind']         = $val->job_kind;
                        $rfidcardReplyAry[$no]['door_check_rule']  = $val->door_check_rule;
                    }
                    $no++;
                }
            }

        }

        //2. 輸出[白名單，ＲＦＩＤ配對，專案卡片配對，人頭像]
        //dd([$whitelistReplyAry,$rfidcardReplyAry,$projectReplyAry]);
        return [$whitelistReplyAry,$rfidcardReplyAry,$projectReplyAry,$headAry];
    }

    /**
     * 取得 車輛白名單 [配合車辨系統開發車]
     * @return bool
     */
    public function getCarWhiteList($b_factory_id = 0)
    {
        $no = 0;
        $lastUpTime = 0;
        $supplyAry = $whitelistAry = [];
        $carViolationAry     = e_violation_contractor::getCarSelect();
        $defDept        = -1;
        $defDeptName    = '中油';

        //職員白名單
        $carData = DB::table('b_car')->where('car_kind',1)->where('isClose','N');
        if($carData->count())
        {
            foreach ($carData->get() as $val)
            {
                //用戶名單
                $supplyAry[$defDept] = [];
                $supplyAry[$defDept]['id']   = $defDept;
                $supplyAry[$defDept]['name'] = $defDeptName;

                //車牌名單
                $whitelistAry[$no] = [];
                $whitelistAry[$no]['id']        = $val->car_no;
                $whitelistAry[$no]['car_id']    = $val->id;
                $whitelistAry[$no]['user_id']   = ($val->b_cust_id > 0) ? $val->b_cust_id : $defDept;
                $whitelistAry[$no]['user_name'] = ($val->b_cust_id > 0) ? be_dept::getName($val->be_dept_id) : $defDeptName;
                $whitelistAry[$no]['rule']      = 'Y';
                $whitelistAry[$no]['memo']      = '';
                if(isset($carViolationAry[$val->id])) {
                    //違規
                    $whitelistAry[$no]['rule'] = 'N';
                    $whitelistAry[$no]['memo'] = $carViolationAry[$val->id];
                }

                //最後異動時間
                $car_updated_at = strtotime($val->updated_at);
                if($lastUpTime < $car_updated_at) $lastUpTime = $car_updated_at;
                $no++;
            }
        }

        //車輛白名單
        $carData = DB::table('view_door_car');
        if($b_factory_id)
        {
            $carData = $carData->where('b_factory_id',$b_factory_id);
        }
        $carAry = $carData->get()->toArray();
        if(count($carAry)) {
            foreach ($carAry as $key => $val) {
                //用戶名單
                $supplyAry[$val->b_supply_id] = [];
                $supplyAry[$val->b_supply_id]['id']   = $val->b_supply_id;
                $supplyAry[$val->b_supply_id]['name'] = $val->supply;
                //車牌名單
                $whitelistAry[$no] = [];
                $whitelistAry[$no]['id'] = $val->car_no;
                $whitelistAry[$no]['car_id'] = $val->b_car_id;
                $whitelistAry[$no]['user_id'] = $val->b_supply_id;
                $whitelistAry[$no]['user_name'] = $val->supply;

                //最後異動時間
                $car_updated_at = strtotime($val->updated_at);
                if($lastUpTime < $car_updated_at) $lastUpTime = $car_updated_at;

                //柴油車
                if($val->oil_kind == 2 && $val->isInspectionExhaust == 'N')
                {
                    $whitelistAry[$no]['rule'] = 'N';
                    $whitelistAry[$no]['memo'] = Lang::get('sys_base.base_11200');//柴油車之驗排氣日已過期
                    //2021-03-03 關閉驗排氣
                    $whitelistAry[$no]['rule'] = 'Y';
                    $whitelistAry[$no]['memo'] = '';
                } elseif(isset($carViolationAry[$val->b_car_id])) {
                    //違規
                    $whitelistAry[$no]['rule'] = 'N';
                    $whitelistAry[$no]['memo'] = $carViolationAry[$val->b_car_id];
                } else {
                    $whitelistAry[$no]['rule'] = 'Y';
                    $whitelistAry[$no]['memo'] = '';
                }

                $no++;
            }
        }
        sort($supplyAry);
        return [$supplyAry,$whitelistAry,$car_updated_at];
    }
}
