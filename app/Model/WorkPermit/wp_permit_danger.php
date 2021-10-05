<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_danger extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_danger';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_danger::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_danger::where('name',$id);
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
        $data = wp_permit_danger::find($id);
        return isset($data->id)? $data->name : '';
    }

    //取得 名稱
    protected  function getContext($id)
    {
        if(!$id) return '';
        $data = wp_permit_danger::find($id);
        return isset($data->id)? $data->context : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_danger::select('id','name')->where('isClose','N')->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
