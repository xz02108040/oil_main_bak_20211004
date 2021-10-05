<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_shift;

/**
 * 工作許可證_班別
 *
 */
trait WorkPermitShiftTrait
{
    /**
     * 新增 工作許可證_班別
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitShift($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_permit_shift();
        $INS->name              = $data->name;
        $INS->stime             = $data->stime;
        $INS->etime             = $data->etime;
        $INS->isAcrossNight     = ($data->isAcrossNight == 'Y')? 'Y' : 'N';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_班別
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitShift($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_shift::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //開始時間
        if(isset($data->stime) && $data->stime && $data->stime !== $UPD->stime)
        {
            $isUp++;
            $UPD->stime = $data->stime;
        }
        //結束時間
        if(isset($data->etime) && $data->etime && $data->etime !== $UPD->etime)
        {
            $isUp++;
            $UPD->etime = $data->etime;
        }
        //排序
        if(isset($data->isAcrossNight) && in_array($data->isAcrossNight,['Y','N']) && $data->isAcrossNight !== $UPD->isAcrossNight)
        {
            $isUp++;
            $UPD->isAcrossNight = $data->isAcrossNight;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
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
     * 取得 工作許可證_班別
     *
     * @return array
     */
    public function getApiWorkPermitListShift()
    {
        $ret = array();
        //取第一層
        $data = wp_permit_shift::orderby('isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
