<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_f;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_license_type;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_factory_e;
use App\Model\User;

/**
 * 工程案件_監造部門
 *
 */
trait EngineeringDeptTrait
{
    /**
     * 新增 工程案件_轄區監造
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringDept($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->be_dept_id)) return $ret;
        if(!count($data->emp)) return $ret;

        foreach ($data->emp as $uid => $val)
        {
            $INS = new e_project_d();
            $INS->e_project_id   = $data->e_project_id;
            $INS->be_dept_id     = $data->be_dept_id;
            $INS->b_cust_id      = $uid;

            $INS->new_user      = $mod_user;
            $INS->mod_user      = $mod_user;
            $ret = ($INS->save())? $INS->id : 0;
        }
        if($ret && isset($data->local) && count($data->local))
        {
            foreach ($data->local as $lid => $val)
            {
                $UPD = [];
                $UPD['e_project_id']    = $data->e_project_id;
                $UPD['b_factory_id']    = b_factory_a::getStoreId($lid);
                $UPD['b_factory_a_id']  = $lid;
                $this->createEngineeringFactory($UPD,$mod_user);
            }
        }

        return $ret;
    }

    /**
     * 修改 工程案件_轄區監造
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringDept($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_d::find($id);
        if(!isset($UPD->be_dept_id)) return $ret;
        //證照
        if(isset($data->be_dept_id) && is_numeric($data->be_dept_id) && $data->be_dept_id !== $UPD->be_dept_id)
        {
            $isUp++;
            $UPD->be_dept_id = $data->be_dept_id;
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
     * 取得 工程案件_轄區監造
     *
     * @return array
     */
    public function getApiEngineeringDeptList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $project    = e_project::getName($pid);
        //取第一層
        $data = e_project_d::
                join('be_dept as ec','e_project_d.be_dept_id','=','ec.id')->
                join('b_factory as s','ec.b_factory_id','=','s.id')->
                join('b_cust as b','b.id','=','e_project_d.b_cust_id')->
                select('e_project_d.*','ec.name','s.name as store','b.name as user')->
                where('e_project_d.e_project_id',$pid)->orderby('e_project_d.isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['project']        = $project;
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工程案件_轄區監造「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringDept($pid, $isDetial = 'N')
    {
        $ret = array();
        if(!$pid) return $ret;
        //監造部門
        $chargeAry = e_project::getChargeDeptUser($pid);
        if(count($chargeAry))
        {
            foreach ($chargeAry as $val)
            {
                $tmp = [];
                $tmp['id']              = $val['id'];
                $tmp['name']            = $val['name'].'('.$val['dept'].')';
                $tmp['dept_id']         = $val['dept_id'];
                //場地
                if($isDetial == 'Y')
                {
                    //$tmp['works_local']   = $this->getApiEngineeringFactory($pid,0,$isDetial);
                    //2021-02-19  取消與工程案件之場地關聯
                    $tmp['works_local']   = b_factory_a::getApiSelect(0,0,$isDetial);
                }
                $ret[] = $tmp;
            }
        }

        //取第一層
        $data = e_project_d::
        join('be_dept as ec','e_project_d.be_dept_id','=','ec.id')->
        join('view_user as u','u.b_cust_id','=','e_project_d.b_cust_id')->
        where('e_project_d.e_project_id',$pid)->where('e_project_d.isClose','N');

        $data = $data->select('u.b_cust_id','u.name','ec.id as dept_id','ec.name as dept')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']              = $v->b_cust_id;
                $tmp['name']            = $v->name.'('.$v->dept.')';
                $tmp['dept_id']         = $v->dept_id;
                //場地
                if($isDetial == 'Y')
                {
                    //$tmp['works_local']   = $this->getApiEngineeringFactory($pid,$v->dept_id,$isDetial);
                    //2021-02-19  取消與工程案件之場地關聯
                    $tmp['works_local']   = b_factory_e::getLocalApiSelect($pid,$v->dept_id,$isDetial);
                }

                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
