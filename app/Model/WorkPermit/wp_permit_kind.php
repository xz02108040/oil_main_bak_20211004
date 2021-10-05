<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_kind extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_kind';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_kind::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_kind::where('name',$id);
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
        $data = wp_permit_kind::find($id);
        return isset($data->id)? $data->name : '';
    }
    //取得 名稱
    protected  function getSubName($id)
    {
        if(!$id) return '';
        $data = wp_permit_kind::find($id);
        return isset($data->id)? $data->sub_name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_kind::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getReptSelect()
    {
        $ret    = [];
        $data   = wp_permit_kind::where('isClose','N')->select('id','name');
        $data   = $data->orderby('show_order')->get();

        foreach ($data as $val)
        {
            $tmp = [];
            $tmp['name']    = $val->name;
            $tmp['amt']     = 0;
            $tmp['item']    = wp_permit_workitem::getReptSelect($val->id);
            $ret[$val->id] = $tmp;
        }

        return $ret;
    }
}
