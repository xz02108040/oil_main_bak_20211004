<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_workitem_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_workitem_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($tid,$did,$extid = 0)
    {
        if(!$tid || !$did) return 0;
        $data = wp_permit_workitem_a::where('wp_permit_workitem_id',$tid)->where('wp_permit_danger_id',$did)->where('isClose','N');

        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getKindID($id)
    {
        if(!$id) return 0;
        $data = wp_permit_workitem_a::find($id);
        return isset($data->id)? $data->wp_permit_kind_id : 0;
    }

    //取得 名稱
    protected  function getItemID($id)
    {
        if(!$id) return 0;
        $data = wp_permit_workitem_a::find($id);
        return isset($data->id)? $data->wp_permit_workitem_id : 0;
    }

    //取得 名稱
    protected  function getDangerID($id)
    {
        if(!$id) return 0;
        $data = wp_permit_workitem_a::find($id);
        return isset($data->id)? $data->wp_permit_danger_id : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($tid,$isApi = 0)
    {
        $ret    = [];
        $data   = wp_permit_workitem_a::where('isClose','N');
        if(is_array($tid))
        {
            $data = $data->whereIn('wp_permit_workitem_id',$tid);
        } else {
            $data = $data->where('wp_permit_workitem_id',$tid);
        }
        $data = $data->select('id','wp_permit_danger_id')->orderby('show_order')->get();
        //$ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']      = $val->wp_permit_danger_id;
                $tmp['name']    = wp_permit_danger::getName($val->wp_permit_danger_id);
                $ret[$val->wp_permit_danger_id] = $tmp;
            } else {
                $ret[$val->wp_permit_danger_id] = wp_permit_danger::getName($val->wp_permit_danger_id);
            }
        }

        return $ret;
    }
}
