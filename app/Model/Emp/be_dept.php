<?php

namespace App\Model\Emp;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Factory\b_factory_a;
use Illuminate\Database\Eloquent\Model;
use Lang;

class be_dept extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'be_dept';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id,$store = 0)
    {
        if(!$id) return 0;
        $data = be_dept::where('id',$id)->where('isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = be_dept::where('name',$id);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data  = be_dept::where('id',$id)->select('name')->first();
        return isset($data->name)? $data->name : '';
    }

    //取得 名稱
    protected  function getParantDept($id)
    {
        if(!$id) return 0;
        $data  = be_dept::where('id',$id)->select('parent_id')->first();
        return isset($data->parent_id)? $data->parent_id : 0;
    }

    //取得 階級
    protected  function getLevel($id)
    {
        if(!$id) return 1;
        $data  = be_dept::where('id',$id)->select('level')->first();
        return isset($data->level)? $data->level : 1;
    }

    //取得 是否為全廠部門
    protected  function isFullField($id)
    {
        if(!$id) return 'N';
        $data  = be_dept::where('id',$id)->select('isFullField')->first();
        return isset($data->isFullField)? $data->isFullField : 'N';
    }

    //取得 下拉選擇全部
    protected  function getSelect($pid = 0, $store = 0, $level = 0, $isEmp = '',$exid = 0,$isfirst = 1,$showType = 1)
    {
        $ret    = [];
        $data   = be_dept::select('id','name','parent_id')->where('isClose','N');
        if($pid)
        {
            $data = $data->where('parent_id',$pid);
        }
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($level)
        {
            $data = $data->where('level',$level);
        }
        if($isEmp)
        {
            $data = $data->where('isEmp',$isEmp);
        }
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }
        $data = $data->orderby('parent_id')->orderby('show_order')->get();
//        dd([$pid,$store,$level,$isEmp,$exid]);
        if($isfirst == 1) $ret[0]  = Lang::get('sys_base.base_10016');
        if($isfirst == 2) $ret[0]  = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $parent = ($val->parent_id)? (be_dept::getName($val->parent_id).'>') : '';
            $ret[$val->id] = ($showType == 1)? $parent.$val->name :$val->name;
        }

        return $ret;
    }



    //取得 下拉選擇全部
    protected  function getApiSelect($store = 0, $level = 0, $isEmp = '')
    {
        $ret  = [];
        $data = be_dept::select('id','name','b_factory_id')->where('isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($level)
        {
            $data = $data->where('level',$level);
        }
        if($isEmp)
        {
            $data = $data->where('isEmp',$isEmp);
        }
        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']              = $val->id;
            $tmp['name']            = $val->name;
            $tmp['b_factory_id']    = $val->b_factory_id;
            $ret[] = $tmp;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getLevelDeptAry($dept_id,$isAll = 'Y')
    {
        $ret  = [];
        $data = be_dept::select('id')->where('isClose','N');
        $data = $data->where('parent_id',$dept_id);
        if($isAll == 'Y') $ret[] = $dept_id;
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[] = $val->id;
            }
        }

        return $ret;
    }
}
