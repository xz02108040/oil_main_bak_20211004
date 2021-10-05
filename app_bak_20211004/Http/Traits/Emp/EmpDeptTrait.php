<?php

namespace App\Http\Traits\Emp;

use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_factory;
use App\Model\User;
use Lang;

/**
 * 組織部門
 *
 */
trait EmpDeptTrait
{
    /**
     * 新增 組織部門
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBeDept($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new be_dept();
        $INS->name          = $data->name;
        $INS->b_factory_id  = ($data->b_factory_id > 0)? $data->b_factory_id : 0;
        $INS->parent_id     = ($data->parent_id > 0)? $data->parent_id : 0;
        $INS->level         = 0;
        $INS->isEmp         = ($data->isEmp == 'Y')? 'Y' : 'N';
        $INS->isFullField   = ($data->isFullField == 'Y')? 'Y' : 'N';
        $INS->show_order    = is_numeric($data->show_order)? $data->show_order : 999;
        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 組織部門
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBeDept($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = be_dept::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //廠區
        if(isset($data->b_factory_id) && is_numeric($data->b_factory_id) && $data->b_factory_id !== $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id = $data->b_factory_id;
        }
        //上一層
        if(isset($data->parent_id) && is_numeric($data->parent_id) && $data->parent_id !== $UPD->parent_id)
        {
            $isUp++;
            $UPD->parent_id = $data->parent_id;
            $UPD->level     = 0;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $UPD->show_order) {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //實體部門
        if(isset($data->isEmp) && in_array($data->isEmp,['Y','N']) && $data->isEmp !== $UPD->isEmp)
        {
            $isUp++;
            $UPD->isEmp = $data->isEmp;
        }
        //負責全廠的部門
        if(isset($data->isFullField) && in_array($data->isFullField,['Y','N']) && $data->isFullField !== $UPD->isFullField)
        {
            $isUp++;
            $UPD->isFullField = $data->isFullField;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = $data->isClose;
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
     * 取得 組織部門
     *
     * @return array
     */
    public function getApiBeDeptList($pid = 0)
    {
        $ret = array();
        $storeAry = b_factory::getSelect();
        $deptAry  = be_dept::getSelect();
        //取第一層
        $data = new be_dept();
        if($pid >= 0)
        {
            $data = $data->where('parent_id',$pid);
        }
        $data = $data->orderby('parent_id')->orderby('show_order')->get();
        if(is_object($data))
        {
            $topDept = Lang::get('sys_base.base_10016');
            foreach ($data as $k => $v)
            {
                $data[$k]['store']       = (isset($storeAry[$v->b_factory_id]))? $storeAry[$v->b_factory_id] : '';
                $data[$k]['parent']      = ($v->parent_id > 0 && isset($deptAry[$v->parent_id]))? $deptAry[$v->parent_id] : $topDept;
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
