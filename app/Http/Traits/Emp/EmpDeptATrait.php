<?php

namespace App\Http\Traits\Emp;

use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_dept_a;
use App\Model\Emp\be_title;
use App\Model\User;

/**
 * 組織部門_職稱
 *
 */
trait EmpDeptATrait
{
    /**
     * 新增 組織部門_職稱
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createBeDeptTitle($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->be_dept_id)) return $ret;

        $INS = new be_dept_a();
        $INS->be_dept_id   = $data->be_dept_id;
        $INS->be_title_id  = $data->be_title_id;
        $INS->new_user     = $mod_user;
        $INS->mod_user     = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 組織部門_職稱
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setBeDeptTitle($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;


        $UPD = be_dept_a::find($id);
        if(!isset($UPD->be_dept_id)) return $ret;
        //部門
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
     * 取得 組織部門_職稱->部門
     *
     * @return array
     */
    public function getApiBeDeptTitleMainList()
    {
        $ret = array();
        //取第一層
        $data   = be_dept_a::join('be_dept','be_dept_a.be_dept_id','=','be_dept.id')->
                  where('be_dept_a.isClose','N')->where('be_dept.isClose','N');
        //Mysql5.7 支援
        //$data   = $data->selectRaw('ANY_VALUE(be_dept_a.be_title_id) as id,ANY_VALUE(be_dept.name) as dept_name');

        //Mysql5.6 支援
        $data   = $data->selectRaw('MAX(be_dept_a.be_title_id) as id,MAX(be_dept.name) as dept_name');

        $data   = $data->orderby('be_dept.show_order')->groupBy('be_dept_id')->get();
        if(is_object($data))
        {
            $ret = $data;
        }

        return $ret;
    }

    /**
     * 取得 組織部門_職稱->職稱
     *
     * @return array
     */
    public function getApiBeDeptTitleSubList($dept_id)
    {
        $ret = array();
        //取第一層
        $data   = be_dept_a::join('be_title','be_dept_a.be_title_id','=','be_title.id')->
        join('be_dept','be_dept_a.be_dept_id','=','be_dept.id')->
        orderby('be_dept_a.isClose')->
        orderby('be_title.show_order','desc')->
        select('be_dept_a.*','be_dept.name as dept_name','be_title.name as dept_title','be_title.isAd as isAd','be_title.show_order as show_order')->
        where('be_dept_a.be_dept_id',$dept_id)->
        where('be_title.isClose','N')->get();

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
