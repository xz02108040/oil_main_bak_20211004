<?php

namespace App\Model\WorkPermit;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_process_title extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_process_title';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($process_id, $be_title_id, $extid = 0)
    {
        if(!$process_id || !$be_title_id) return 0;
        $data = wp_permit_process_title::where('wp_permit_process_id',$process_id)->where('be_title_id',$be_title_id)->
                where('isClose','N');

        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();

        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function isTitleTarget($process_id,$self_title)
    {
        $ret = 1;
        $app = [];
        if(!$process_id) return $ret;

        $data = wp_permit_process_title::select('be_title_id')->where('wp_permit_process_id',$process_id)->
                where('isClose','N');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $app[] = $val->be_title_id;
            }
            if(!in_array($self_title,$app)) $ret = 0;

        }
        return $ret;
    }

    //取得 名稱
    protected  function getTitleTargetName($process_id)
    {
        $ret = [];
        if(!$process_id) return '';

        $data = wp_permit_process_title::join('be_title as t','t.id','=','be_title_id')->
                where('wp_permit_process_id',$process_id)->where('wp_permit_process_title.isClose','N')->
                select('t.id','t.name');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[$val->id] = $val->name;
            }
        }

        return count($ret)? implode('，',$ret) : '';
    }
}
