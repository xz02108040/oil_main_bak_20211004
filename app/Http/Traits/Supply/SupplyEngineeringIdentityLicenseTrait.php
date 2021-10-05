<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_violation_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_engineering_identity_a;
use App\Model\User;

/**
 * 承攬商成員＿工程身分
 *
 */
trait SupplyEngineeringIdentityLicenseTrait
{
    /**
     * 新增 承攬商成員＿工程身分＿證照
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyEngineeringIdentityLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_engineering_identity_id)) return $ret;

        $INS = new b_supply_engineering_identity_a();
        $INS->b_supply_engineering_identity_id  = $data->b_supply_engineering_identity_id;
        $INS->e_license_id                      = $data->e_license_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 承攬商成員＿工程身分＿證照
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyEngineeringIdentityLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_engineering_identity_a::find($id);
        if(!isset($UPD->id)) return $ret;
        //證照
        if(isset($data->e_license_id) && is_numeric($data->e_license_id) && $data->e_license_id !== $UPD->e_license_id)
        {
            $isUp++;
            $UPD->e_license_id = $data->e_license_id;
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
     * 取得 承攬商成員＿工程身分＿證照
     *
     * @return array
     */
    public function getApiSupplyEngineeringIdentityLicenseList($iid)
    {
        $ret = array();
        //取第一層
        $data = e_license::join('e_license_type as et','e_license.license_type','=','et.id')->
                join('b_supply_engineering_identity as i','i.id','=','e_license.engineering_identity_id')->
                where('i.id',$iid)->where('e_license.isClose','N')->where('i.isClose','N')->
                select('i.name as b_supply_engineering_identity','e_license.name as e_license','et.name as e_license_type');

        if($data->count())
        {
            $ret = $data->get();
        }

        return $ret;
    }

}
