<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_line extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_line';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id,$pipeline_id)
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_line::where('wp_work_id',$wp_work_id)->where('wp_permit_pipeline_id',$pipeline_id)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }


    //取得 下拉選擇全部
    protected  function getSelect($wid)
    {
        $wp_permit_line = wp_permit_pipeline::getSelect(0);
        $ret    = [];
        $data   = wp_work_line::where('wp_work_id',$wid)->
                select('id','wp_permit_pipeline_id','memo')->where('isClose','N')->get();

        foreach ($data as $key => $val)
        {

            $memo = (in_array($val->wp_permit_pipeline_id,[1]))? $val->memo : (isset($wp_permit_line[$val->wp_permit_pipeline_id])? $wp_permit_line[$val->wp_permit_pipeline_id] : '');
            $ret[$val->wp_permit_pipeline_id] = $memo;
        }

        return $ret;
    }
}
