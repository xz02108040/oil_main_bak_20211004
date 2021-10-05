<?php

namespace App\Http\Traits\Report;

use App\Lib\HtmlLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory;
use App\Model\Report\rept_doorinout_car_t;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\View\view_user;
use Storage;
use DB;
use Lang;

/**
 * 報表：當日 人員異常統計
 *
 */
trait ReptDoorInOutErrTrait
{

    /**
     * 產生 當日 廠區儀表板 資料
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function genDoorInOutErrApi($level = 1,$mode = 'M',$store_id = 0,$door_id = 0,$project_id = 0,$supply_id = 0,$today = '')
    {
        $reptAry= [];
        if(!$today) $today = date('Y-m-d');
        //資料
        $data = LogLib::getTodayInputErrLog($mode,$store_id,$door_id,$project_id,$supply_id,$today,0,$level);

        if($level == 1)
        {
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['e_project_id']= $val->e_project_id;
                $tmp['headline']    = e_project::getName($val->e_project_id);
                $tmp['title']       = $val->store;
                $tmp['amt']         = $val->amt;
                $tmp['unit']        = Lang::get('sys_base.base_40221');
                $tmp['sub_title']   = $val->door;
                $reptAry[]          = $tmp;
            }
        } else {
            $doorTypeAry = SHCSLib::getCode('DOOR_INOUT_TYPE');
            foreach ($data as $val)
            {
                $tmp = [];
                $tmp['headline']    = e_project::getName($val->e_project_id);
                $tmp['title']       = $val->unit_name;
                $tmp['sub_title']   = $val->door;
                $tmp['name']        = $val->name;
                $tmp['door_type']   = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                $tmp['door_stamp']  = substr($val->door_stamp,0,19);
                $tmp['err_code']    = $val->door_memo;
                $reptAry[]          = $tmp;
            }
        }

        return $reptAry;
    }
}
