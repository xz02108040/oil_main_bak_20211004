<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * 廠區_轄區部門
            *
 */
trait FactoryDeptTrait
{
    /**
     * 新增 廠區_轄區部門
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createFactoryDept($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_factory_a_id)) return $ret;

        $INS = new b_factory_e();
        $INS->b_factory_id      = $data->b_factory_id ? $data->b_factory_id : 0;
        $INS->b_factory_a_id    = $data->b_factory_a_id ? $data->b_factory_a_id : 0;
        $INS->be_dept_id        = $data->be_dept_id ? $data->be_dept_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 廠區_轄區部門
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setFactoryDept($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_factory_e::find($id);
        if(!isset($UPD->be_dept_id)) return $ret;
        //廠區
        if(isset($data->be_dept_id) && is_numeric($data->be_dept_id) && $data->be_dept_id !==  $UPD->be_dept_id)
        {
            $isUp++;
            $UPD->be_dept_id = $data->be_dept_id;
        }

        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
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
     * 取得 廠區_轄區部門
     *
     * @return array
     */
    public function getApiFactoryDeptList($factory_a_id = 0)
    {
        $ret = array();
        $storeAry = b_factory::getSelect();
        $storeLocalAry = b_factory_a::getSelect();
        $deptAry  = be_dept::getSelect();
        //取第一層
        $data = b_factory_e::orderby('isClose')->orderby('id','desc');
        if($factory_a_id >= 0)
        {
            $data = $data->where('b_factory_a_id',$factory_a_id);
        }
        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['factory']            = isset($storeAry[$v->b_factory_id])? $storeAry[$v->b_factory_id] : '';
                $data[$k]['factory_a']          = isset($storeLocalAry[$v->b_factory_a_id])? $storeLocalAry[$v->b_factory_a_id] : '';
                $data[$k]['dept']               = isset($deptAry[$v->be_dept_id])? $deptAry[$v->be_dept_id] : '';
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 廠區_轄區部門
     *
     * @return array
     */
    public function getApiFactoryDept($factory_a_id = 0,$isApp = 0)
    {
        $ret = array();
        //取第一層
        $data = b_factory_e::join('be_dept as e','e.id','=','b_factory_e.be_dept_id')->
            orderby('b_factory_e.isClose')->orderby('e.id','desc');
        if($factory_a_id >= 0)
        {
            $data = $data->where('b_factory_a_id',$factory_a_id);
        }
        $data = $data->select('e.id','e.name')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']      = $v->id;
                $tmp['name']    = $v->name;
                if($isApp)
                {
                    $tmp['member']    = $this->getApiEmp($v->id);
                }
                $ret[] = $tmp;
            }
        }

        return $ret;
    }
}
