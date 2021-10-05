<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_process_topic;

/**
 * 工作許可證_流程_簽核身份
 *
 */
trait WorkPermitProcessTargetTrait
{
    /**
     * 新增 工作許可證_流程_簽核身份
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitProcessTarget($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        $INS = new wp_permit_process_target();
        $INS->wp_permit_id          = $data->wp_permit_id;
        $INS->wp_permit_process_id  = $data->wp_permit_process_id;
        $INS->bc_type_app_id        = $data->bc_type_app_id;
        $INS->show_order            = ($data->show_order > 0)? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_流程_簽核身份
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitProcessTarget($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_process_target::find($id);
        if(!isset($UPD->wp_permit_id)) return $ret;

        //種類
        if(isset($data->bc_type_app_id) && $data->bc_type_app_id > 0 && $data->bc_type_app_id !== $UPD->bc_type_app_id)
        {
            $isUp++;
            $UPD->bc_type_app_id = $data->bc_type_app_id;
        }
        //排序
        if(isset($data->show_order) && $data->show_order > 0 && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
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
     * 取得 工作許可證_流程_簽核身份
     *
     * @return array
     */
    public function getApiWorkPermitProcessTargetList($pid)
    {
        $ret = array();
        $mainAry    = wp_permit::getSelect(0);
        $typeAry    = bc_type_app::getSelect(0);
        //取第一層
        $data = wp_permit_process_target::where('wp_permit_process_id',$pid)->orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_permit']      = isset($mainAry[$v->wp_permit_id])? $mainAry[$v->wp_permit_id] : '';
                $data[$k]['bc_type_app']    = isset($typeAry[$v->bc_type_app_id])?  $typeAry[$v->bc_type_app_id] : '';
                $data[$k]['bc_type']        = wp_permit_process::getBcType($v->wp_permit_process_id);
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
