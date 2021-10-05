<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_check extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_check';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_check::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_check::where('name',$id);
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
        $data = wp_check::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 wp_check_kind_id
    protected  function getKindID($id)
    {
        if(!$id) return '';
        $data = wp_check::where('id',$id)->select('wp_check_kind_id')->first();
        return (isset($data->wp_check_kind_id))? $data->wp_check_kind_id : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_check::select('id','name')->where('isClose','N')->orderby('show_order')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
