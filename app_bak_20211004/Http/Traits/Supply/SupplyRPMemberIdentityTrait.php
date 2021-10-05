<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_rp_member_ei;
use App\Model\User;

/**
 * 承攬商_申請_成職員程身分
 *
 */
trait SupplyRPMemberIdentityTrait
{
    /**
     * 申請 承攬商_申請_成職員程身分
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPMemberIdentity($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;
        $INS = new b_supply_rp_member_ei();
        $INS->b_supply_id                    = $data->b_supply_id;
        $INS->apply_user                     = $mod_user;
        $INS->apply_stamp                    = date('Y-m-d H:i:s');
        $INS->b_cust_id                      = $data->b_cust_id;
        $INS->engineering_identity_id        = $data->type_id ? $data->type_id : 0;
        $INS->b_supply_rp_member_id          = isset($data->b_supply_rp_member_id) ? $data->b_supply_rp_member_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {

            if(isset($data->license_id) && isset($data->edate) && isset($data->file1))
            {
                $data->b_supply_member_ei_id = $ret;
                $this->createSupplyRPMemberLicense($data,$mod_user);
            }

        }
        return $ret;
    }

    /**
     * 修改 承攬商_申請_成職員程身分
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPMemberIdentity($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $aprocAry = array_keys(SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC'));
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = b_supply_rp_member_ei::find($id);
        if(!isset($UPD->engineering_identity_id)) return $ret;
        //審查結果
        if(isset($data->aproc) && in_array($data->aproc,$aprocAry) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = isset($data->charge_memo)? $data->charge_memo : '';
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //審查通過
            if($data->aproc == 'O' && isset($data->isIdentity) && $data->isIdentity)
            {
                if(!$this->createSupplyMemberIdentity($data,$mod_user))
                {
                    $isUp--;
                }
            }
        }
        //工程身分
        if(isset($data->type_id) && ($data->type_id) && $data->type_id !== $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->type_id;
        }
        //停用
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose        = 'Y';
                $UPD->close_user     = $mod_user;
                $UPD->close_stamp    = $now;
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
     * 取得 承攬商_成員申請單 by 承攬商
     *
     * @return array
     */
    public function getApiSupplyRPMemberIdentityMainList($aproc = 'A',$rp_member_id = 0,$rp_project_id = 0)
    {
        $data = b_supply_rp_member_ei::join('b_supply as s','s.id','=','b_supply_rp_member_ei.b_supply_id')->
            selectRaw('MAX(s.id) as b_supply_id,MAX(s.name) as b_supply,count(s.id) as amt')->
            where('b_supply_rp_member_ei.b_supply_rp_member_id',$rp_member_id)->
            where('b_supply_rp_member_ei.b_supply_rp_project_id',$rp_project_id)->
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
     * 取得 承攬商_申請_成職員程身分
     *
     * @return array
     */
    public function getApiSupplyRPMemberIdentityList($sid,$rp_member_id = 0,$rp_project_id = 0,$aproc = 'A')
    {
        $ret = array();
        $typeAry  = b_supply_engineering_identity::getSelect();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //取第一層
        $data = b_supply_rp_member_ei::where('b_supply_id',$sid)->where('isClose','N');
        if($rp_member_id >= 0)
        {
            $data    = $data->where('b_supply_rp_member_id',$rp_member_id);
        }
        if($rp_project_id >= 0)
        {
            $data    = $data->where('b_supply_rp_project_id',$rp_project_id);
        }
        //進度
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }

        $data    = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['type']        = isset($typeAry[$v->engineering_identity_id])? $typeAry[$v->engineering_identity_id] : '';
                $data[$k]['aproc_name']  = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_name']  = User::getName($v->apply_user);
                $data[$k]['user']        = User::getName($v->b_cust_id);
                $data[$k]['charge_name'] = User::getName($v->charge_user);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);

            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
