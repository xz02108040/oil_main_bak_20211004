<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_title;
use App\Model\Report\rept_doorinout_day;
use App\Model\Report\rept_doorinout_time;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：門禁累計時數
 *
 */
trait ReptDoorInOutDailyTrait
{
    /**
     * 取得 當日累計進出時間統計
     */
    public function getDoorInOutDailyList($b_factory_id,$sdate,$edate,$b_supply_id = 0,$uid = 0,$isGroup = 'N')
    {
        $ret = [];
        if(!$b_factory_id) return [0,$ret];
        if(!$sdate) $sdate = date('Y-m-01');
        if(!$edate) $edate = date('Y-m-31');

        $data = rept_doorinout_day::where('b_factory_id',$b_factory_id)->where('door_date','>=',$sdate)->where('door_date','<=',$edate);

        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($uid)
        {
            $data = $data->where('b_cust_id',$uid);
        }
        //dd($b_factory_id,$sdate,$edate,$b_supply_id,$uid,$data->count());
        if($data->count())
        {
            if($isGroup == 'Y')
            {
                $data = $data->selectRaw('door_date,supply,COUNT(id) as amt,SUM(total_times) as total_times')->
                                groupby('door_date');
            }
            $ret = $data->get();
        }
        return [$data->count(),$ret];
    }

    /**
     * 取得 當日累計進出時間統計
     */
    public function getDoorInOutTimesList($b_factory_id,$sdate,$edate,$b_supply_id = 0,$uid = 0)
    {
        $ret = [];
        if(!$b_factory_id) return [0,$ret];
        if(!$sdate) $sdate = date('Y-m-01');
        if(!$edate) $edate = date('Y-m-31');

        $data = rept_doorinout_time::where('b_factory_id',$b_factory_id)->where('door_date1','>=',$sdate)->where('door_date1','<=',$edate);
        $data = $data->where('isClose','N');
        if($b_supply_id)
        {
            $data = $data->where('b_supply_id',$b_supply_id);
        }
        if($uid)
        {
            $data = $data->where('b_cust_id',$uid);
        }
        //dd($b_factory_id,$sdate,$edate,$b_supply_id,$uid,$data->count());
        if($data->count())
        {
            $ret = $data->get();
        }
        return [$data->count(),$ret];
    }
    /**
     * 新增 當日進出時間統計
     * @return bool
     */
    public function genDoorInOutTimes($b_factory_id = 0,$today = '',$sid = 0,$uid = 0,$mod_user = '1000000000')
    {
        $logAry = [];
        if(!$today) $today = date('Y-m-d');
        $tomorrow = SHCSLib::addDay(1,$today);
        $min_time = sys_param::getParam('DOOR_SHIFT_MIN_TIME1');
        $max_time = sys_param::getParam('DOOR_SHIFT_MAX_TIME2');
        //今日　早班　跟晚班
        $data = DB::table('log_door_inout')->where('b_factory_id',$b_factory_id);
        $data = $data->where('door_stamp','>=',$today.' '.$min_time);
        $data = $data->where('door_stamp','<=',$tomorrow.' '.$max_time);
        if($sid)
        {
            $data = $data->where('b_supply_id',$sid);
        }
        if($uid)
        {
            $data = $data->where('b_cust_id',$uid);
        }
        $data = $data->whereIn('door_type',[1,2])->select('b_factory_id','b_supply_id','b_cust_id','name','unit_name','door_type','door_date','door_stamp')->
                orderby('b_cust_id')->orderby('door_stamp');
//        dd($today,$tomorrow,$sid,$uid,$data->count());
        if($data->count())
        {
            $data = $data->get();
            //第一階段：先整理　早晚班進出紀錄
            foreach( $data as $value)
            {
                $uid = $value->b_cust_id;

                if($value->door_type == 2)
                {
                    //離場
                    $key = (isset($logAry[$uid]))? count($logAry[$uid]) : 0;
                    if($key && isset($logAry[$uid][$key]['door_stamp1']))
                    {
                        $door_stamp1 = $logAry[$uid][$key]['door_stamp1'];

                        $logAry[$uid][$key]['door_date2']   = $value->door_date;
                        $logAry[$uid][$key]['door_stamp2']  = $value->door_stamp;
                        $logAry[$uid][$key]['result']       = 'Y'; //找到相對應的進場紀錄
                        $logAry[$uid][$key]['door_times']   = SHCSLib::getTime($door_stamp1,$value->door_stamp); //計算時間
                    }
                } else {
                    //進場
                    $key = (isset($logAry[$uid]))? count($logAry[$uid])+1 : 1;
                    $tmp = [];
                    $tmp['b_cust_id'] = $uid;
                    $tmp['b_factory_id'] = $value->b_factory_id;
                    $tmp['b_supply_id'] = $value->b_supply_id;
                    $tmp['supply'] = $value->unit_name;
                    $tmp['name'] = $value->name;
                    $tmp['door_date1']  = $value->door_date;
                    $tmp['door_stamp1'] = $value->door_stamp;
                    $tmp['door_date2']  = '0000-00-00';
                    $tmp['door_stamp2'] = '0000-00-00 00:00:00';
                    $tmp['result'] = 'N';
                    $tmp['door_times']   = 0;
                    $logAry[$uid][$key] = $tmp;
                }
            }
            if(count($logAry))
            {
                //dd($logAry);
                foreach ($logAry as $uid => $logval)
                {
                    $firstNo = 1;
                    foreach ($logval as $logData)
                    {
                        if($firstNo == 1)
                        {
                            //關閉該人員記錄
                            $this->closeDoorInOutTimes($logData['b_factory_id'],$logData['door_date1'],$logData['b_cust_id'],$mod_user);
                            $firstNo = 0;
                        }
                        $INS = new rept_doorinout_time();
                        $INS->b_factory_id  = $logData['b_factory_id'];
                        $INS->b_supply_id   = $logData['b_supply_id'];
                        $INS->supply        = $logData['supply'];
                        $INS->b_cust_id     = $logData['b_cust_id'];
                        $INS->name          = $logData['name'];
                        $INS->new_user      = $mod_user;
                        $INS->result        = $logData['result'];
                        $INS->door_times    = $logData['door_times'];
                        $INS->door_date1    = $logData['door_date1'];
                        $INS->door_stamp1   = $logData['door_stamp1'];
                        $INS->door_date2    = $logData['door_date2'];
                        $INS->door_stamp2   = $logData['door_stamp2'];

                        $INS->mod_user      = $mod_user;
                        $ret = ($INS->save())? $INS->id : 0;
                    }
                }
            }
        }

        return [count($logAry),$logAry];
    }

    /**
     * 關閉 當日進出時間統計
     * @return bool
     */
    public function closeDoorInOutTimes($b_factory_id,$today,$uid,$mod_user = '1000000000')
    {
        if(!$b_factory_id || !$today || !$uid) return false;
        $UPD = [];
        $UPD['isClose']     = 'Y';
        $UPD['close_user']  = $mod_user;
        $UPD['close_stamp'] = date('Y-m-d H:i:s');

        $data = DB::table('rept_doorinout_time')->where('b_factory_id',$b_factory_id)->
                where('door_date1',$today)->where('b_cust_id',$uid)->where('isClose','N');
        return $data->update($UPD);
    }

    /**
     * 新增 當日累計進出時間統計
     * @return bool
     */
    public function genDoorInOutDaily($b_factory_id = 0,$today = '',$sid = 0,$mod_user = '1000000000')
    {
        $logAry = [];
        if(!$today) $today = date('Y-m-d');
        //今日　早班　跟晚班
        $data = rept_doorinout_time::where('b_factory_id',$b_factory_id);
        $data = $data->where('door_date1',$today)->where('isClose','N');
        if($sid)
        {
            $data = $data->where('b_supply_id',$sid);
        }
        $data = $data->selectRaw('SUM(door_times) as total_times,b_factory_id,b_supply_id,b_cust_id,supply,name,door_date1');
        $data = $data->groupBy('b_cust_id');
        if($data->count())
        {
            $data = $data->get();
            $logAry = $data;
            //第一階段：先累加　早晚班進出紀錄
            foreach( $data as $value)
            {
                $logid = rept_doorinout_day::isExist($value->b_factory_id,$value->door_date1,$value->b_cust_id);
                if($logid)
                {
                    $INS = rept_doorinout_day::find($logid);
                } else {
                    $INS = new rept_doorinout_day();
                    $INS->b_factory_id  = $value->b_factory_id;
                    $INS->b_supply_id   = $value->b_supply_id;
                    $INS->supply        = $value->supply;
                    $INS->b_cust_id     = $value->b_cust_id;
                    $INS->name          = $value->name;
                    $INS->door_date     = $value->door_date1;
                    $INS->new_user      = $mod_user;
                }
                $INS->total_times   = $value->total_times;
                $INS->mod_user      = $mod_user;
                $ret = ($INS->save())? $INS->id : 0;

            }
        }

        return [count($logAry),$logAry];
    }
}
