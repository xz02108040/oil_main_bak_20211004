<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_dept;

/**
 * 工單 區域部門
 *
 */
trait WorkPermitDeptTrait
{
    /**
     * 新增 工單 區域部門
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitDept($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new wp_work_dept();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->be_dept_id            = $data->be_dept_id;
        $INS->name                  = $data->name;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工單 區域部門
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitDept($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_dept::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->be_dept_id) && $data->be_dept_id && $data->be_dept_id !== $UPD->be_dept_id)
        {
            $isUp++;
            $UPD->be_dept_id = $data->be_dept_id;
            $UPD->name       = be_dept::getName($data->be_dept_id);
        }
        //簽核人
        if(isset($data->charge_user) && is_numeric($data->charge_user) && $data->charge_user !== $UPD->charge_user)
        {
            $isUp++;
            $UPD->charge_user = $data->charge_user;
            $UPD->charge_stamp = date('Y-m-d H:i:s');
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

            //更新回work 尚未簽核的區域部門
            $no_charge_dept     = wp_work_dept::getSelect($UPD->wp_work_id,'N');
            $no_charge_deptAry  = array_keys($no_charge_dept);

            $TMP = [];
            $TMP['no_charge_dept4'] = implode(',',$no_charge_deptAry);
            $this->setWorkPermitWorkOrder($UPD->wp_work_id,$TMP,$mod_user);
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 工單工作項目
     *
     * @return array
     */
    public function getApiWorkPermitDeptList()
    {
        $ret = array();
        //取第一層
        $data = wp_work_dept::orderby('isClose')->get();
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

}
