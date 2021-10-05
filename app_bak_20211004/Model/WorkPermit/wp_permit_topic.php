<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_topic extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_topic';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id, $name,$extid = 0)
    {
        if(!$id || !$name) return 0;
        $data = wp_permit_topic::where('wp_permit_id',$id)->where('name',$name);
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
        $data = wp_permit_topic::find($id);
        return (isset($data->id))? $data->name : '';
    }
    //取得 題目類型
    protected  function getType($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic::find($id);
        return (isset($data->id))? $data->wp_topic_type : 0;
    }

    //取得 名稱
    protected  function getFullName($id,$icon = '')
    {
        if(!$id) return '';
        $data = wp_permit_topic::find($id);
        return isset($data->id)? wp_permit::getName($data->wp_permit_id).$icon.$data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_topic::select('id','name')->where('isClose','N')->orderby('show_order')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
