<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * 廠區
 *
 */
trait FactoryTrait
{
    /**
     * 新增 廠區
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createFactory($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new b_factory();
        $INS->name          = $data->name;
        $INS->boss_id       = $data->boss_id ? $data->boss_id : 0;
        $INS->sdate         = $data->sdate ? $data->sdate : date('Y-m-d');
        $INS->edate         = $data->edate ? $data->edate : '9999-12-31';
        $INS->address       = $data->address ? $data->address : '';
        $INS->tel1          = $data->tel1 ? $data->tel1 : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 廠區
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setFactory($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_factory::find($id);
        if(!isset($UPD->name)) return $ret;
        //公司名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //廠長
        if(isset($data->boss_id) && is_numeric($data->boss_id) && $data->boss_id !==  $UPD->boss_id)
        {
            $isUp++;
            $UPD->boss_id = $data->boss_id;
        }
        //開始日期
        if(isset($data->sdate) && strlen($data->sdate) && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //結束日期
        if(isset($data->edate) && strlen($data->edate) && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //地址
        if(isset($data->tel1) && $data->tel1 !==  $UPD->tel1)
        {
            $isUp++;
            $UPD->tel1 = $data->tel1;
        }
        //地址
        if(isset($data->address) && $data->address !==  $UPD->address)
        {
            $isUp++;
            $UPD->address = $data->address;
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
     * 取得 廠區
     *
     * @return array
     */
    public function getApiFactoryList()
    {
        $ret = array();
        //取第一層
        $data = b_factory::orderby('isClose')->orderby('id','desc')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['boss']        = User::getName($v->boss_id);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
