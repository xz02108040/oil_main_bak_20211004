<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\User;

/**
 * 承攬商
 *
 */
trait SupplyTrait
{
    /**
     * 新增 承攬商
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupply($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new b_supply();
        $INS->name          = $data->name;
        $INS->sub_name      = $data->sub_name;
        $INS->tax_num       = $data->tax_num ? $data->tax_num : '';
        $INS->boss_name     = $data->boss_name ? $data->boss_name : '';
        $INS->tel1          = $data->tel1 ? $data->tel1 : '';
        $INS->tel2          = $data->tel2 ? $data->tel2 : '';
        $INS->fax1          = $data->fax1 ? $data->fax1 : '';
        $INS->fax2          = $data->fax2 ? $data->fax2 : '';
        $INS->email         = $data->email ? $data->email : '';
        $INS->address       = $data->address ? $data->address : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 承攬商
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupply($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply::find($id);
        if(!isset($UPD->name)) return $ret;
        //公司名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //公司名稱
        if(isset($data->sub_name) && $data->sub_name && $data->sub_name !== $UPD->sub_name)
        {
            $isUp++;
            $UPD->sub_name = $data->sub_name;
        }
        //統編
        if(isset($data->tax_num) && is_numeric($data->tax_num) && $data->tax_num !== $UPD->tax_num)
        {
            $isUp++;
            $UPD->tax_num = $data->tax_num;
        }
        //負責人
        if(isset($data->boss_name) && $data->boss_name !== $UPD->boss_name)
        {
            $isUp++;
            $UPD->boss_name = $data->boss_name;
        }
        //電話1
        if(isset($data->tel1) && $data->tel1 !== $UPD->tel1)
        {
            $isUp++;
            $UPD->tel1 = $data->tel1;
        }
        //電話2
        if(isset($data->tel2) && $data->tel2 !== $UPD->tel2)
        {
            $isUp++;
            $UPD->tel2 = $data->tel2;
        }
        //傳真1
        if(isset($data->fax1) && $data->fax1 !== $UPD->fax1)
        {
            $isUp++;
            $UPD->fax1 = $data->fax1;
        }
        //傳真2
        if(isset($data->fax2) && $data->fax2 !== $UPD->fax2)
        {
            $isUp++;
            $UPD->fax2 = $data->fax2;
        }
        //信箱
        if(isset($data->email) && $data->email !== $UPD->email)
        {
            $isUp++;
            $UPD->email = $data->email;
        }
        //地址
        if(isset($data->address) && $data->address !== $UPD->address)
        {
            $isUp++;
            $UPD->address = $data->address;
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

                //需要停用所有該 承攬商成員
                b_supply_member::setClose($id,$mod_user);
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
     * 取得 承攬商
     *
     * @return array
     */
    public function getApiSupplyList()
    {
        $ret = array();
        //取第一層
        $data = b_supply::orderby('isClose')->orderby('id','desc')->get();

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

    /**
     * 取得 承攬商 For APP
     *
     * @return array
     */
    public function getApiSupplyData($supply_id ,$supply_name = '', $isDetail = 'Y')
    {
        $ret = array();
        //取第一層
        $data = b_supply::where('isClose','N')->
                select('id','name','tax_num','boss_name','tel1','tel2','fax1','fax2','email','address');
        if($supply_id)
        {
            $data = $data->where('id',$supply_id);
        }
        if($supply_name)
        {
            $data = $data->where(function ($query) use ($supply_name) {
                $query->where('name','like','%'.$supply_name.'%')->orWhere('tax_num','like','%'.$supply_name.'%');
            });
        }
        $data = $data->first();
        if(isset($data->id))
        {
            if($isDetail == 'Y')
            {
                //承攬商成員
                $data['member']     = $this->getApiSupplyMember($data->id,'Y');
                $data['project']    = $this->getApiSupplyEngineering($data->id,'Y');
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
