<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\et_traning_time;
use App\Model\User;

/**
 * 課程時段
 *
 */
trait TraningTimeTrait
{
    /**
     * 新增 課程時段
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createTraningTime($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->week)) return $ret;

        $INS = new et_traning_time();
        $INS->et_traning_id  = $data->et_traning_id;
        $INS->week           = $data->week ? $data->week : 0;
        $INS->stime          = $data->stime ? $data->stime : '';
        $INS->etime          = $data->etime ? $data->etime : '';
        $INS->memo           = $data->memo ? $data->memo : '';

        $INS->new_user       = $mod_user;
        $INS->mod_user       = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 課程時段
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setTraningTime($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = et_traning_time::find($id);
        if(!isset($UPD->week)) return $ret;
        //週
        if(isset($data->week) && is_numeric($data->week) && $data->week !==  $UPD->week)
        {
            $isUp++;
            $UPD->week = $data->week;
        }
        //開始時間
        if(isset($data->stime) && CheckLib::isTime($data->stime) && $data->stime !==  $UPD->stime)
        {
            $isUp++;
            $UPD->stime = $data->stime;
        }
        //結束時間
        if(isset($data->etime) && CheckLib::isTime($data->etime) && $data->etime !==  $UPD->etime)
        {
            $isUp++;
            $UPD->etime = $data->etime;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 課程時段
     *
     * @return array
     */
    public function getApiTraningTimeList()
    {
        $ret = array();
        $weekAry = SHCSLib::getCode('WEEK');
        //取第一層
        $data = et_traning_time::orderby('isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['stime']       = substr($v->stime,0,5);
                $data[$k]['etime']       = substr($v->etime,0,5);
                $data[$k]['week_name']   = isset($weekAry[$v->week])? $weekAry[$v->week] : '';
                $data[$k]['week_name']   = isset($weekAry[$v->week])? $weekAry[$v->week] : '';
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
