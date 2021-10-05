<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_check_topic extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_check_topic';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_check_topic::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id, $name,$extid = 0)
    {
        if(!$id || !$name) return 0;
        $data = wp_check_topic::where('wp_check_id',$id)->where('name',$name);
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
        $data = wp_check_topic::find($id);
        return (isset($data->id))? $data->name : '';
    }
    //取得 名稱
    protected  function getCircle($id)
    {
        if(!$id) return 0;
        $data = wp_check_topic::find($id);
        return (isset($data->id))? $data->circle : 0;
    }

    //取得 名稱
    protected  function getFullName($id,$icon = '')
    {
        if(!$id) return '';
        $data = wp_check_topic::find($id);
        return isset($data->id)? wp_check::getName($data->wp_check_id).$icon.$data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($kid, $isFirst = 1)
    {
        $ret    = [];
        $data   = wp_check_topic::where('wp_check_id',$kid)->select('id','name')->where('isClose','N')->orderby('show_order')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
