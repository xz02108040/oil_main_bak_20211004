<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * 廠區_場地
 *
 */
trait FactoryLocalTrait
{
    /**
     * 新增 廠區_場地
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createFactoryLocal($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new b_factory_a();
        $INS->name          = $data->name;
        $INS->b_factory_id  = $data->b_factory_id ? $data->b_factory_id : 0;
        $INS->kind          = $data->kind ? $data->kind : '';
        $INS->memo          = $data->memo ? $data->memo : '';
        $INS->GPSX          = (isset($data->GPSX) && $data->GPSX) ? $data->GPSX : '';
        $INS->GPSY          = (isset($data->GPSY) && $data->GPSY) ? $data->GPSY : '';
        $INS->voice_box_ip  = (isset($data->voice_box_ip) && $data->voice_box_ip) ? $data->voice_box_ip : '';

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
    public function setFactoryLocal($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_factory_a::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //廠區
        if(isset($data->b_factory_id) && is_numeric($data->b_factory_id) && $data->b_factory_id !==  $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id = $data->b_factory_id;
        }
        //種類
        if(isset($data->kind) && strlen($data->kind) && $data->kind !==  $UPD->kind)
        {
            $isUp++;
            $UPD->kind = $data->kind;
        }
        //IP
        if(isset($data->voice_box_ip) && $data->voice_box_ip !==  $UPD->voice_box_ip)
        {
            $isUp++;
            $UPD->voice_box_ip = $data->voice_box_ip;
        }
        //說明
        if(isset($data->memo) && $data->memo !==  $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //GPSX
        if(isset($data->GPSX) && $data->GPSX !==  $UPD->GPSX)
        {
            $isUp++;
            $UPD->GPSX = $data->GPSX;
        }
        //GPSY
        if(isset($data->GPSY) && $data->GPSY !==  $UPD->GPSY)
        {
            $isUp++;
            $UPD->GPSY = $data->GPSY;
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
    public function getApiFactoryLocalList($store = 0,$kind = 0)
    {
        $ret = array();
        $storeAry = b_factory::getSelect();
        $kindAry  = SHCSLib::getCode('FACORY_LOCL_TYPE');
        //取第一層
        $data = b_factory_a::orderby('isClose')->orderby('id','desc');
        if(is_numeric($store) && $store > 0)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($kind)
        {
            $data = $data->where('kind',$kind);
        }
        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['factory']        = isset($storeAry[$v->b_factory_id])? $storeAry[$v->b_factory_id] : '';
                $data[$k]['kind_name']      = isset($kindAry[$v->kind])? $kindAry[$v->kind] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
