<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：當日 違規
 *
 */
trait ReptViolationTodayTrait
{
    /**
     * 當日各廠區儀表板(廠區/門別/承攬商)
     * @param $door_date
     * @param $b_factory_id
     * @param $b_factory_d_id
     * @param $supply
     * @param $door_type
     * @return array|object
     */
    public function getViolationTodayData($door_date = '', $suuply_id = 0, $isCount = 'N')
    {
        $ret = [];
        $door_date = (!$door_date) ? date('Y-m-d') : $door_date;
        $doorTypeAry = SHCSLib::getCode('DOOR_INOUT_TYPE');
        $nowStamp = time();
        $yesterday = SHCSLib::addDay(-1, $door_date);
        $yesterdaySTime = sys_param::getParam('REPORT_DOOR_YESTERDAY_STIME', '00:00:00');
        $yesterdayETime = sys_param::getParam('REPORT_DOOR_YESTERDAY_ETIME', '08:00:00');
        $todaySTime = sys_param::getParam('REPORT_DOOR_TODAY_STIME', '08:00:00');
        $y_sdate = date('Y-m-d H:i:s', strtotime($yesterday . ' ' . $yesterdaySTime));
        $y_edate = date('Y-m-d H:i:s', strtotime($door_date . ' ' . $yesterdayETime));
        $t_sdate = date('Y-m-d H:i:s', strtotime($door_date . ' ' . $todaySTime));
        $t_edate = date('Y-m-d H:i:s', strtotime($door_date . ' ' . $yesterdaySTime));
        $t_Stamp1 = strtotime($t_sdate);
        $t_Stamp2 = strtotime($t_edate);
        //$doorColorAry= [0=>5,1=>2,2=>4];
        //搜尋 當日進出廠紀錄
        $data = rept_doorinout_t::join('b_factory as f', 'f.id', '=', 'b_factory_id')->
        join('b_factory_d as d', 'd.id', '=', 'b_factory_d_id');


        if ($suuply_id) {
            $data = $data->where('rept_doorinout_t.b_supply_id', $suuply_id);
        }

        //if($level > 1)dd($b_factory_id,$b_factory_d_id,$suuply_id,$level,$data->get());
        if ($isCount == 'Y') {
            $ret = $data->count();
        } else {
            if ($data->count()) {
                if ($level == 4) {
                    foreach ($data->get() as $val) {
                        $workRoot = wp_work_worker::getWorkInfo($val->wp_work_id);
                        $imgPath = LogLib::getLogDoorImgUrl($val->log_id);
                        if ($val->log_id) {
                            if (substr($imgPath, 0, 4) != 'http') {
                                $imgPath = url('img/Door/' . SHCSLib::encode($val->log_id));
                            }
                        }
                        $tmp = [];
                        $tmp['id'] = $val->log_id;
                        $tmp['name'] = $val->name;
                        $tmp['store'] = $val->store;
                        $tmp['door'] = $val->door;
                        $tmp['door_stamp'] = substr($val->door_stamp, 0, 16);
                        $tmp['unit_name'] = $val->unit_name;
                        $tmp['door_type'] = $val->door_type;
                        $tmp['door_type_name'] = isset($doorTypeAry[$val->door_type]) ? $doorTypeAry[$val->door_type] : '';
                        $tmp['img'] = $imgPath;
                        $tmp['permit_no'] = isset($workRoot['no']) ? $workRoot['no'] : '';
                        $tmp['worker1'] = isset($workRoot['worker1']) ? $workRoot['worker1'] : '';
                        $tmp['worker2'] = isset($workRoot['worker2']) ? $workRoot['worker2'] : '';
                        $tmp['job_kind'] = LogLib::getJobKind($val->log_id);
                        $ret[] = $tmp;
                    }
                } else {
                    $ret = $data->get()->toArray();
                }
            }
        }

        return $ret;
    }
}
