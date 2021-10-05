<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_license_type;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_engineering_identity_a;
use App\Model\User;

/**
 * 工程案件_工程身份
 *
 */
trait EngineeringLicenseTrait
{
    /**
     * 新增 工程案件_工程身份
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringLicense($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->engineering_identity_id)) return $ret;

        $INS = new e_project_l();
        $INS->e_project_id              = $data->e_project_id;
        $INS->engineering_identity_id   = $data->engineering_identity_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工程案件_工程身份
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringLicense($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = e_project_l::find($id);
        if(!isset($UPD->engineering_identity_id)) return $ret;
        //工程身份
        if(isset($data->engineering_identity_id) && is_numeric($data->engineering_identity_id) && $data->engineering_identity_id != $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->engineering_identity_id;
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
     * 取得 工程案件_工程身份
     *
     * @return array
     */
    public function getApiEngineeringLicenseList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $project    = e_project::getName($pid);
        $identityAry= b_supply_engineering_identity::getSelect(0);
        //取第一層
        $data = e_project_l::
                where('e_project_l.e_project_id',$pid)->orderby('e_project_l.isClose')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['project']     = $project;
                $data[$k]['identity']    = isset($identityAry[$v->engineering_identity_id])? $identityAry[$v->engineering_identity_id] : '';
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工程案件_工程身份「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringLicense($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        //取第一層
        $data = e_project_l::
        join('b_supply_engineering_identity as el','e_project_l.engineering_identity_id','=','el.id')->
        select('el.id','el.name as name')->
        where('e_project_l.e_project_id',$pid)->where('e_project_l.isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']              = $v->id;
                $tmp['name']            = $v->name;
                $ret[$v->id] = $tmp;
            }
        }

        return $ret;
    }

}
