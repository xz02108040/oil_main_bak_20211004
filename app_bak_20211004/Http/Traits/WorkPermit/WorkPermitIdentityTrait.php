<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_identity;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_work_topic;

/**
 * 工作許可證_流程
 *
 */
trait WorkPermitIdentityTrait
{
    /**
     * 新增 工作許可證_工程身份
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitIdentity($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        $INS = new wp_permit_identity();
        $INS->wp_permit_id              = ($data->wp_permit_id > 0)? $data->wp_permit_id : 0;
        $INS->engineering_identity_id   = ($data->engineering_identity_id > 0)? $data->engineering_identity_id : 0;
        $INS->least_amt                 = ($data->least_amt >= 0)? $data->least_amt : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_工程身份
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitIdentity($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_identity::find($id);
        if(!isset($UPD->wp_permit_id)) return $ret;

        //工程身份
        if(isset($data->engineering_identity_id) && $data->engineering_identity_id > 0 && $data->engineering_identity_id !== $UPD->engineering_identity_id)
        {
            $isUp++;
            $UPD->engineering_identity_id = $data->engineering_identity_id;
        }
        //至少需要人數
        if(isset($data->least_amt) && $data->least_amt >= 0 && $data->least_amt !== $UPD->least_amt)
        {
            $isUp++;
            $UPD->least_amt = $data->least_amt;
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
     * 取得 工作許可證_工程身份
     *
     * @return array
     */
    public function getApiWorkPermitIdentityList($id)
    {
        $ret = array();
        $mainAry    = wp_permit::getSelect();
        $idAry      = b_supply_engineering_identity::getSelect(0);

        //取第一層
        $data = wp_permit_identity::where('wp_permit_id',$id)->orderby('isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_permit']      = isset($mainAry[$v->wp_permit_id])? $mainAry[$v->wp_permit_id] : '';
                $data[$k]['name']           = isset($idAry[$v->engineering_identity_id])? $idAry[$v->engineering_identity_id] : '';
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_工程身份
     *
     * @return array
     */
    public function getApiWorkPermitIdentity($id,$extAry = [1,2])
    {
        $ret    = array();
        $idAry  = b_supply_engineering_identity::getSelect(0);

        //取第一層
        $data = wp_permit_identity::where('wp_permit_id',$id)->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('engineering_identity_id',$extAry);
        }

        $data = $data->select('engineering_identity_id')->orderby('engineering_identity_id')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $index = $v->engineering_identity_id;
                $ret[$index]['id']   = $index;
                $ret[$index]['name'] = isset($idAry[$v->engineering_identity_id])? $idAry[$v->engineering_identity_id] : '';
            }
            $ret = (object)$ret;
        }

        return $ret;
    }

}
