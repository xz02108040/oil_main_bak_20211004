<?php

namespace App\Http\Traits\Supply;

use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_license_type;
use App\Model\Supply\b_supply_car_type;
use App\Model\Supply\b_supply_rp_new_license;
use App\Model\sys_param;
use App\Model\User;
use Storage;
use Lang;

/**
 * 承攬商_車輛申請
 *
 */
trait SupplyRPNewLicenseTrait
{
    /**
     * 新增 車輛申請
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPNewLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id)) return $ret;

        $INS = new b_supply_rp_new_license();
        $INS->apply_user            = $mod_user;
        $INS->apply_stamp           = date('Y-m-d H:i:s');
        $INS->b_supply_id           = $data->b_supply_id;
        $INS->license_name          = $data->license_name;
        $INS->license_type          = $data->license_type;
        $INS->edate_limit_year1     = $data->edate_limit_year1;
        $INS->edate_limit_year2     = $data->edate_limit_year2;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 車輛申請
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPNewLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_new_license::find($id);
        if(!isset($UPD->id)) return $ret;


        if(isset($data->aproc) && $data->aproc && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = strlen($data->charge_memo)? $data->charge_memo : '';
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //審查通過
            if($data->aproc == 'O')
            {
                $INS = [];
                $INS['name']                = $UPD->license_name;
                $INS['license_issuing_kind']= 1;
                $INS['license_type']        = $UPD->license_type;
                $INS['sdate']               = '';
                $INS['edate']               = '';
                $INS['license_show_name1']  = Lang::get('sys_engineering.engineering_72');
                $INS['license_show_name2']  = Lang::get('sys_engineering.engineering_133');
                $INS['license_show_name3']  = Lang::get('sys_engineering.engineering_134');
                $INS['license_show_name4']  = Lang::get('sys_engineering.engineering_134');
                $INS['license_show_name4']  = Lang::get('sys_engineering.engineering_135');
                $INS['license_issuing_kind4']  = 2;
                $INS['edate_limit_year1']   = $UPD->edate_limit_year1;
                $INS['edate_limit_year2']   = $UPD->edate_limit_year2;
                $INS['edate_type']          = '';

                if(!$this->createLicense($INS,$mod_user))
                {
                    $isUp--;
                }
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
     * 取得 車輛申請
     *
     * @return array
     */
    public function getApiSupplyRPNewLicenseList($sid,$aproc = 'A')
    {
        $ret = array();
        $typeAry  = e_license_type::getSelect();
        $aprocAry = SHCSLib::getCode('RP_SUPPLY_MEMBER_APROC');
        //取第一層
        $data = b_supply_rp_new_license::where('aproc',$aproc);
        if($sid)
        {
            $data = $data->where('b_supply_id',$sid);
        }

        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['license_type_name']  = isset($typeAry[$v->license_type])? $typeAry[$v->license_type] : '';
                $data[$k]['aproc_name']         = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_name']         = User::getName($v->apply_user);
                $data[$k]['apply_stamp']        = substr($v->apply_stamp,0,16);
                $data[$k]['charge_stamp']       = substr($v->charge_stamp,0,16);
                $data[$k]['charge_name']        = User::getName($v->charge_user);
                $data[$k]['chg_user']           = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
