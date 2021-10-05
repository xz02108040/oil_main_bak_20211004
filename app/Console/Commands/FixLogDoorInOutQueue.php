<?php

namespace App\Console\Commands;

use App\Lib\LogLib;
use App\Model\Engineering\e_project;
use App\Model\View\view_log_door_today;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class FixLogDoorInOutQueue
 * 修正若工安、工負已刷入，施工人員刷卡出現工安、工負未到廠的門禁進出紀錄資料
 * 1. 離線狀態工安、工負已刷入，恢復線上工安、工負刷卡紀錄尚未補入，施工人員刷卡出現錯誤
 * 2. 系統 bug 造成未偵測到已刷入之工安、工負
 */
class FixLogDoorInOutQueue extends Command
{
    // 命令名稱
    protected $signature = 'httc:fixlogdoorinout';

    // 說明文字
    protected $description = '[FixLogDoorInOut] 修正門禁進出紀錄資料';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        $startTime = time();
        $ret = ['fixed_log_ids' => [], 'cron_start'=>date('Y-m-d H:i:s', $startTime)];
        $tmp = [];

        // 查詢當日門禁紀錄
        $resArr = view_log_door_today::where('err_code', 4)->where('door_stamp', '<=', date('Y-m-d H:i:s'))->get();
        foreach ($resArr as $res) {
            $jobUserInCount = 0;
            $safeUserInCount = 0;
            list($door_rule, $jobUserAry, $safeUserAry)  = e_project::getDoorRule($res->e_project_id);
            switch ($door_rule) {
                case 2: // 依據案件之工安、工負
                    $jobUserInCount = $this->getUserInCount($jobUserAry, $res->e_project_id, $res->b_factory_id, $res->b_factory_d_id, $res->door_stamp);
                    $safeUserInCount = $this->getUserInCount($safeUserAry, $res->e_project_id, $res->b_factory_id, $res->b_factory_d_id, $res->door_stamp);
                    break;
                case 3: // 依據工作許可證之工安、工負
                    $jobUserAry  = wp_work_worker::getSelect($res->wp_work_id, 1, 0);
                    $safeUserAry = wp_work_worker::getSelect($res->wp_work_id, 2, 0);
                    $jobUserInCount = $this->getUserInCount($jobUserAry, $res->e_project_id, $res->b_factory_id, $res->b_factory_d_id, $res->door_stamp);
                    $safeUserInCount = $this->getUserInCount($safeUserAry, $res->e_project_id, $res->b_factory_id, $res->b_factory_d_id, $res->door_stamp);
                    break;
            }
            if (!empty($jobUserInCount) > 0 && !empty($safeUserInCount) > 0) {
                DB::table('log_door_inout')->where('id', $res->log_id)->update(['door_result' => 'Y', 'door_memo' => '', 'err_code' => 0]);
                $ret['fixed_log_ids'][] = $res->log_id;
            }else{
                $tmp[] = $res->log_id;
            }
        }

        $endTime = time();
        $ret['cron_end'] = date('Y-m-d H:i:s', $endTime);
        $ret['cron_runtime'] = $endTime - $startTime;

        //2. Log
        LogLib::putCronLog('FixLogDoorInOut','Y',json_encode($ret));
    }

    /**
     * 取得使用者入廠人數
     * @param $userArr 判斷的使用者清單
     * @param $project_id 案件 ID
     * @param $factory_id 廠區 ID
     * @param $factory_d_id 門別 ID
     * @param $door_stamp 判斷的刷卡時間
     */
    private function getUserInCount($userArr, $project_id, $factory_id, $factory_d_id, $door_stamp)
    {
        $userInArr = [];
        $userLogArr = view_log_door_today::whereIn('unit_id', $userArr)
        ->where('b_factory_id', $factory_id)
        ->where('b_factory_d_id', $factory_d_id)
        ->where('e_project_id', $project_id)
        ->where('door_result', 'Y')
        ->where('door_stamp', '<', $door_stamp) // 排除超過刷卡時間的資料，避免誤判
        ->orderBy('door_stamp', 'asc')
        ->get();
        if ($userLogArr) { // 紀錄在廠內的使用者 b_cust_id 清單
            foreach ($userLogArr as $userLog) {
                if ($userLog->door_type == 1) { // 進廠
                    $userInArr[$userLog->unit_id] = 1;
                }
                if ($userLog->door_type == 2) { // 出廠
                    unset($userInArr[$userLog->unit_id]);
                }
            }
        }
        return count($userInArr);
    }

}
