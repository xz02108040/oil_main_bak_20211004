<?php

namespace App\Model\Emp;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_cust_e extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_cust_e';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_cust_id';

    protected $guarded = ['b_cust_id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = b_cust_e::where('b_cust_id',$id)->first();
        return (isset($data->b_cust_id))? $data->b_cust_id : 0;
    }

    //[職員編號]是否存在
    protected  function isEmpNoExist($no,$extid = 0)
    {
        if(!$no) return 0;
        $data = b_cust_e::where('emp_no',$no)->select('b_cust_id');
        if($extid)
        {
            $data = $data->where('b_cust_id','!=',$extid);
        }
        $data = $data->first();
        return (isset($data->b_cust_id))? $data->b_cust_id : 0;
    }
    //
    protected  function getloginInfo($empno)
    {
        $ret = [0,0];
        if(!$empno) return $ret;
        $data = b_cust_e::where('emp_no',$empno)->select('b_cust_id','be_dept_id')->first();
        return isset($data->be_dept_id)? [$data->b_cust_id,$data->be_dept_id] : $ret;
    }
    //
    protected  function getEmpInfo($id)
    {
        $ret = [0,0];
        if(!$id) return $ret;
        $data = b_cust_e::where('b_cust_id',$id)->select('be_title_id','be_dept_id')->first();
        return isset($data->be_dept_id)? [$data->be_dept_id,$data->be_title_id] : $ret;
    }
    //簽核腳色
    protected  function getTitle($b_cust_id)
    {
        $ret = [];
        if(!$b_cust_id) return $ret;
        $data = b_cust_e::where('b_cust_id',$b_cust_id)->select('be_title_id')->first();
        return isset($data->be_title_id)? $data->be_title_id : 0;
    }
    //取得我的代理人陣列
    protected  function getAttorneyAry($b_cust_id,$isSelf = 0)
    {
        $ret = [];
        if(!$b_cust_id) return $ret;
        if($isSelf) $ret[] = $b_cust_id;
        $data = b_cust_e::where('attorney_id',$b_cust_id)->select('b_cust_id');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[] = $val->b_cust_id;
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($store = 0,$dept = 0,$isSelfItem = 0,$isExtUid = 0,$isSE = '',$isFirst = 1)
    {
        $ret    = [];
        $data   = b_cust_e::join('b_cust','b_cust.id','=','b_cust_e.b_cust_id')
            ->join('be_title','be_title.id','=','b_cust_e.be_title_id')
            ->select('b_cust.id','b_cust.name','b_cust_e.be_level','be_title.name as be_title')
            ->where('isVacate','N')->where('b_cust.isClose','N');
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($dept)
        {
            $data = $data->where('be_dept_id',$dept);
        }
        if($isSE)
        {
            $data = $data->where('isSE',$isSE);
        }
        //排除特定人員
        if($isExtUid)
        {
            $data = $data->where('b_cust_id','!=',$isExtUid);
        }
        $data = $data->orderby('be_level')->get();


        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        //選擇 自己選項
        if($isSelfItem)
        {
            $ret[-1] = Lang::get('sys_emp.emp_102');
        }

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name.'['.$val->be_title.']';
        }

        return $ret;
    }
}
