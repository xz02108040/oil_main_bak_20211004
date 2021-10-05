<?php

namespace App\Http\Traits\Emp;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\User;
use App\Model\View\view_dept_member;

/**
 * 組織職員
 *
 */
trait EmpTrait
{
    /**
     * 新增 組織職員
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEmp($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data) && !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_cust_id)) return $ret;

        $INS = new b_cust_e();
        $INS->b_cust_id         = $data->b_cust_id;
        $INS->emp_no            = isset($data->emp_no)? $data->emp_no : 'EMP'.time();
        $INS->b_factory_id      = $data->b_factory_id;
        $INS->be_dept_id        = $data->be_dept_id;
        $INS->be_title_id       = $data->be_title_id;
        $INS->be_level          = 0;
        $INS->boss_id           = ($data->boss_id)? $data->boss_id : 0;
        $INS->attorney_id       = 0;
        $INS->be_sdate          = date('Y-m-d');
        $INS->be_edate          = '9999-12-31';
        $INS->isSE              = isset($data->isSE)? $data->isSE : 'N';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;

        return ($INS->save())? $data->b_cust_id : 0;
    }

    /**
     * 修改 組織職員
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEmp($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_cust_e::find($id);
        if(!isset($UPD->be_title_id)) return $ret;
        //職員編號
        if(isset($data->emp_no) && $data->emp_no && $data->emp_no !== $UPD->emp_no)
        {
            $isUp++;
            $UPD->emp_no = $data->emp_no;
        }
        //代理人
        if(isset($data->attorney_id) && $data->attorney_id && $data->attorney_id !== $UPD->attorney_id)
        {
            $isUp++;
            $UPD->attorney_id = $data->attorney_id;
        }
        //主管
        if(isset($data->boss_id) && is_numeric($data->boss_id) && $data->boss_id !== $UPD->boss_id)
        {
            $isUp++;
            $UPD->boss_id = $data->boss_id;
        }
        //門市
        if(isset($data->b_factory_id) && is_numeric($data->b_factory_id) && $data->b_factory_id !== $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id = $data->b_factory_id;
        }
        //門市
        if(isset($data->be_dept_id) && is_numeric($data->be_dept_id) && $data->be_dept_id !== $UPD->be_dept_id)
        {
            $isUp++;
            $UPD->be_dept_id = $data->be_dept_id;
        }
        //職稱
        if(isset($data->be_title_id) && is_numeric($data->be_title_id) && $data->be_title_id !== $UPD->be_title_id)
        {
            $isUp++;
            $UPD->be_title_id = $data->be_title_id;
        }
        //啟用日期
        if(isset($data->be_sdate) && ($data->be_sdate) && $data->be_sdate !== $UPD->be_sdate)
        {
            $isUp++;
            $UPD->be_sdate = $data->be_sdate;
        }
        //離職日期
        if(isset($data->be_edate) && ($data->be_edate) && $data->be_edate !== $UPD->be_edate)
        {
            $isUp++;
            $UPD->be_edate = $data->be_edate;
        }
        //監造身分
        if(isset($data->isSE) && in_array($data->isSE,['Y','N']) && $data->isSE !== $UPD->isSE)
        {
            $isUp++;
            $UPD->isSE = $data->isSE;
        }
        //離職
        if(isset($data->isVacate) && in_array($data->isVacate,['Y','N']) && $data->isVacate !== $UPD->isVacate)
        {
            $isUp++;
            if($data->isVacate == 'Y')
            {
                $UPD->isVacate       = $data->isVacate;
                $UPD->vacate_type    = isset($data->vacate_type)? $data->vacate_type : 0;
                $UPD->vacate_memo    = isset($data->vacate_memo)? $data->vacate_memo : '';

                $UPD->vacate_user    = $mod_user;
                $UPD->vacate_stamp   = $now;

                //更新回 帳號
                $TMP = [];
                $TMP['isClose'] = 'Y';
                $this->setBcust($id,$TMP,$mod_user);

            } else {
                $UPD->isVacate = 'N';
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
     * 取得 組織職員
     *
     * @return array
     */
    public function getApiEmpList($search = [0,0,0,'',''])
    {
        $ret = array();
        list($store_id,$det_id,$title_id,$name,$emp_no) = $search;
        //取第一層
        $data = b_cust_e::join('b_cust','b_cust.id','=','b_cust_e.b_cust_id')
                ->join('be_dept','be_dept.id','=','b_cust_e.be_dept_id')
                ->join('be_title','be_title.id','=','b_cust_e.be_title_id')
                ->select('b_cust_e.*','b_cust.name','be_dept.name as dept','be_title.name as title','b_cust.isLogin','b_cust.isClose','b_cust.close_user','b_cust.close_stamp');
        if($store_id)
        {
            $data = $data->where('b_cust_e.b_factory_id',$store_id);
        }
        if($det_id)
        {
            $data = $data->where('b_cust_e.be_dept_id',$det_id);
        }
        if($title_id)
        {
            $data = $data->where('b_cust_e.be_title_id',$title_id);
        }
        if($name)
        {
            $data = $data->where('b_cust.name','like','%'.$name.'%');
        }
        if($emp_no)
        {
            $data = $data->where('b_cust_e.emp_no','like','%'.$emp_no.'%');
        }

        $data = $data->orderby('b_cust_e.isVacate')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['vacate_user'] = User::getName($v->vacate_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 組織職員
     *
     * @return array
     */
    public function getApiEmp($det_id)
    {
        $ret = array();
        //取第一層
        $data = view_dept_member::where('be_dept_id',$det_id)->
                select('b_cust_id','name');

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']      = $v->b_cust_id;
                $tmp['name']    = $v->name;
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
