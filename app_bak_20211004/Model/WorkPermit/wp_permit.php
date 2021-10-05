<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit::where('name',$id);
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
        $data = wp_permit::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 種類
    protected  function getAt($id)
    {
        if(!$id) return 0;
        $data = wp_permit::find($id);
        return (isset($data->id))? $data->permit_at : 0;
    }

    //取得 種類
    protected  function updateAt($id = 1)
    {
        if(!$id) return 0;
        $data = wp_permit::find($id);
        if(isset($data->id))
        {
            $data->permit_at = $data->permit_at + 1;
            $data->save();
        }
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit::select('id','name')->where('isClose','N')->orderby('show_order')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
