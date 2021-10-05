<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Supply\b_supply_rp_bcust;
use App\Model\sys_param;
use App\Model\User;
use Storage;
use Lang;

/**
 * 承攬商_申請_成員帳號
 *
 */
trait SupplyRPBcustTrait
{
    /**
     * 新增 承攬商_申請_成員帳號
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPBcust($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;

        $INS = new b_supply_rp_bcust();
        $INS->apply_user    = $mod_user;
        $INS->apply_stamp   = date('Y-m-d H:i:s');
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_cust_id        = $data->b_cust_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 承攬商_申請_成員帳號
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPBcust($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $aprocAry  = array_keys(SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC'));
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_bcust::find($id);
        if(!isset($UPD->b_cust_id)) return $ret;

        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = $data->charge_memo;
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //審查通過
            if($data->aproc == 'O')
            {
                $def_auth_id = sys_param::getParam('BCUST_SUPPLY_AUTH',1);
                $upAry = [];
                $upAry['isLogin']           = 'Y';
                $upAry['c_menu_group_id']   = $def_auth_id;

                if(!$this->setBcust($UPD->b_cust_id,$upAry,$mod_user))
                {
                    $isUp--;
                }
            }
        }
        //車牌
//        if(isset($data->b_cust_id) && ($data->b_cust_id) && $data->b_cust_id !== $UPD->b_cust_id)
//        {
//            $isUp++;
//            $UPD->b_cust_id = $data->b_cust_id;
//        }

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
     * 取得 承攬商_申請_成員帳號 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyRPBcustMainList($aproc = 'A')
    {
        $data = b_supply_rp_bcust::join('b_supply as s','s.id','=','b_supply_rp_bcust.b_supply_id')->
        selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
        groupby('b_supply_id');

        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        $data = $data->get();
        if(is_object($data)) {
            $ret = (object)$data;
        }
        return $ret;
    }

    /**
     * 取得 承攬商_申請_成員帳號
     *
     * @return array
     */
    public function getApiSupplyRPBcustList($sid,$aproc = 'A')
    {
        $ret = array();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //取第一層
        $data = b_supply_rp_bcust::where('b_supply_id',$sid)->where('aproc',$aproc);

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $id        = $v->id;

                $data[$k]['aproc_name']     = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_name']     = User::getName($v->apply_user);
                $data[$k]['charge_name']    = User::getName($v->charge_user);
                $data[$k]['user']           = User::getName($v->b_cust_id);
                $data[$k]['chg_user']       = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
