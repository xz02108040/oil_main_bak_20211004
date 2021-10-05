<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_process_topic extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_process_topic';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_process_topic::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_process_topic::where('name',$id);
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
        $data = wp_permit_process_topic::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($pid ,$isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_process_topic::where('wp_permit_process_id',$pid)->
                  select('wp_permit_topic_id')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->wp_permit_topic_id] = $val->wp_permit_topic_id;
        }

        return $ret;
    }
}
