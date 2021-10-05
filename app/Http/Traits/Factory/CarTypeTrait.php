<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Factory\b_car_type;
use App\Model\User;

/**
 * 車輛分類
 *
 */
trait CarTypeTrait
{
    /**
     * 新增 車輛分類
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createCarType($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new b_car_type();
        $INS->name          = $data->name;
        $INS->oil_kind      = $data->oil_kind ? $data->oil_kind : 1;
        $INS->show_order    = $data->show_order ? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 車輛分類
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setCarType($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_car_type::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //排序
        if(isset($data->oil_kind) && is_numeric($data->oil_kind) && $data->oil_kind !==  $UPD->oil_kind)
        {
            $isUp++;
            $UPD->oil_kind = $data->oil_kind;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !==  $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
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
     * 取得 車輛分類
     *
     * @return array
     */
    public function getApiCarTypeList()
    {
        $ret = array();
        $oilkindAry = SHCSLib::getCode('CAR_OIL_KIND');
        //取第一層
        $data = b_car_type::orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['oil_kind_name']  = isset($oilkindAry[$v->oil_kind])? $oilkindAry[$v->oil_kind] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
