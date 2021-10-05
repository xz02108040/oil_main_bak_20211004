<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_dept extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_dept';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id,$dept_id)
    {
        if(!$wp_work_id || !$dept_id) return 0;
        $data = wp_work_dept::where('wp_work_id',$wp_work_id)->where('be_dept_id',$dept_id)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }


    //取得 下拉選擇全部
    protected  function getName($wid,$isCharge = '')
    {
        $deptAry = be_dept::getSelect();
        unset($deptAry[0]);
        $ret    = '';
        $data   = wp_work_dept::where('wp_work_id',$wid)->
                select('id','be_dept_id')->where('isClose','N');
        if($isCharge == 'Y')
        {
            $data = $data->where('charge_user','>',0);
        }
        if($isCharge == 'N')
        {
            $data = $data->where('charge_user',0);
        }

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                if($ret) $ret .= '，';
                $ret .= isset($deptAry[$val->be_dept_id])? $deptAry[$val->be_dept_id] : '';
            }
        }


        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($wid,$isCharge = '')
    {
        $deptAry = be_dept::getSelect();
        unset($deptAry[0]);
        $ret    = [];
        $data   = wp_work_dept::where('wp_work_id',$wid)->
                select('id','be_dept_id')->where('isClose','N');
        if($isCharge == 'Y')
        {
            $data = $data->where('charge_user','>',0);
        }
        if($isCharge == 'N')
        {
            $data = $data->where('charge_user',0);
        }

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                $ret[$val->be_dept_id] = isset($deptAry[$val->be_dept_id])? $deptAry[$val->be_dept_id] : '';
            }
        }


        return $ret;
    }
}
