<?php

namespace App\Http\Traits\Supply;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply_rp_door1_a;
use Storage;
use App\Model\User;

/**
 * 承攬商[臨時入場/過夜] 申請單_[成員/車輛]
 *
 */
trait SupplyRPDoor1DetailTrait
{
    /**
     * 新增 承攬商_申請_成員帳號
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPDoor1Detail($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_rp_door1_id)) return $ret;

        $INS = new b_supply_rp_door1_a();
        $INS->b_supply_rp_door1_id  = $data->b_supply_rp_door1_id;
        $INS->b_cust_id             = $data->b_cust_id;
        $INS->b_car_id              = $data->b_car_id;
        $INS->job_kind              = $data->job_kind;
        $INS->cpc_tag               = $data->cpc_tag;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }
    /**
     * 修改 車輛[承攬商,職員]
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPDoor1Detail($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_door1_a::find($id);
        if(!isset($UPD->b_supply_rp_door1_id)) return $ret;

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
     * 取得 車輛[承攬商,職員]
     *
     * @return array
     */
    public function getApiSupplyRPDoor1DetailList($b_supply_rp_door1_id)
    {
        $ret = array();
        //取第一層
        $data = b_supply_rp_door1_a::where('b_supply_rp_door1_id',$b_supply_rp_door1_id)->where('isClose','N');

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['user']       = $v->b_cust_id? User::getName($v->b_cust_id) : b_car::getNo($v->b_car_id);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
