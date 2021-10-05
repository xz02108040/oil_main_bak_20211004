<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_d;
use UUID;
use App\Model\User;

/**
 * 廠區_門禁工作站
 *
 */
trait FactoryDoorTrait
{
    /**
     * 新增 廠區_場地
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createFactoryDoor($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
        $now = time();

        $INS = new b_factory_d();
        $INS->name          = $data->name;
        $INS->b_factory_id  = $data->b_factory_id ? $data->b_factory_id : 0;
        $INS->door_type     = $data->door_type ? $data->door_type : 1;
        $INS->door_account  = $data->door_account ? $data->door_account : '';
        $INS->door_pwd      = (isset($data->door_pwd) && $data->door_pwd) ? $data->door_pwd : UUID::generate(1,'factory_door'.$now,Uuid::NS_DNS)->string;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 廠區_場地
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setFactoryDoor($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_factory_d::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //門禁規則
        if(isset($data->door_type) && is_numeric($data->door_type) && $data->door_type !==  $UPD->door_type)
        {
            $isUp++;
            $UPD->door_type = $data->door_type;
        }
        //帳號
        if(isset($data->door_account) && strlen($data->door_account) && $data->door_account !==  $UPD->door_account)
        {
            $isUp++;
            $UPD->door_account = $data->door_account;
        }
        //密碼
        if(isset($data->door_pwd) && strlen($data->door_pwd) && $data->door_pwd !==  $UPD->door_pwd)
        {
            $isUp++;
            $UPD->door_pwd = $data->door_pwd;
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
     * 取得 廠區_場地
     *
     * @return array
     */
    public function getApiFactoryDoorList($store = 0)
    {
        $ret = array();
        $storeAry = b_factory::getSelect();
        $doorAry  = SHCSLib::getCode('DOOR_CONTROL',1);
        //取第一層
        $data = b_factory_d::orderby('isClose')->orderby('id','desc');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['factory']        = isset($storeAry[$v->b_factory_id])? $storeAry[$v->b_factory_id] : '';
                $data[$k]['door_type_name'] = isset($doorAry[$v->door_type])? $doorAry[$v->door_type] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
