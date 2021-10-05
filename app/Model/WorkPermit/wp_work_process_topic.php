<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_process_topic extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_process_topic';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id,$wp_work_list_id,$wp_permit_topic_a_id)
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_process_topic::where('wp_work_id',$wp_work_id)->where('wp_work_list_id',$wp_work_list_id)->
        where('wp_permit_topic_a_id',$wp_permit_topic_a_id)->where('isClose','N')->first();
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_work_process_topic::where('name',$id);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //是否存在
    protected  function getWorkCheckId($wp_work_id,$wp_work_list_id,$wp_permit_topic_a_id)
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_process_topic::where('wp_work_id',$wp_work_id)->where('wp_work_list_id',$wp_work_list_id)->
        where('wp_permit_topic_a_id',$wp_permit_topic_a_id)->where('isClose','N')->first();
        return (isset($data->id))? $data->wp_work_check_topic_id : 0;
    }

    //取得 名稱
    protected  function getTopicAns($work_id,$wp_permit_topic_a_id,$wp_work_process_id = 0,$img_resize = 0)
    {
        $ret = '';
        if(!$work_id || !$wp_permit_topic_a_id) return $ret;
        $data = wp_work_process_topic::where('wp_work_id',$work_id)->where('wp_permit_topic_a_id',$wp_permit_topic_a_id)->where('isClose','N');
        if($wp_work_process_id)
        {
            $data = $data->where('wp_work_process_id',$wp_work_process_id);
        }
        $data = $data->first();
        if(isset($data->id))
        {
            if($data->isImg == 'Y')
            {
                $ret = ($data->wp_work_img_id)? SHCSLib::toImgBase64String('permit',$data->wp_work_img_id,$img_resize) : '';
            } else {
                $ret = $data->ans_value;
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_work_process_topic::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
