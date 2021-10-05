<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_topic_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_topic_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id,$wp_work_list_id,$wp_permit_topic_a_id)
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_topic_a::where('wp_work_id',$wp_work_id)->where('wp_work_list_id',$wp_work_list_id)->
                where('wp_permit_topic_a_id',$wp_permit_topic_a_id)->where('isClose','N')->first();
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_work_topic_a::where('name',$id);
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
        $data = wp_work_topic_a::find($id);
        return (isset($data->id))? $data->name : '';
    }

    //取得 名稱
    protected  function getImg($id)
    {
        if(!$id) return '';
        $data = wp_work_topic_a::where('id',$id)->where('isImg','Y')->first();
        return (isset($data->id))? $data->ans_value : '';
    }

    //取得 名稱
    protected  function chgTopicAns($work_id,$permit_topic_a_id,$chValue = '')
    {
        if(!$work_id || !$permit_topic_a_id) return '';

        $UPD = [];
        $UPD['ans_value'] = $chValue;

        return wp_work_topic_a::where('wp_work_id',$work_id)->where('wp_permit_topic_a_id',$permit_topic_a_id)->where('isClose','N')->update($UPD);
    }

    //取得 名稱
    protected  function getTopicAns($work_id,$permit_topic_a_id , $img_resize = 0)
    {
        $ret = $created_at = '';
        $charger_id = 0;
        if(!$work_id || !$permit_topic_a_id) return $ret;
        $data = wp_work_topic_a::where('wp_work_id',$work_id)->where('wp_permit_topic_a_id',$permit_topic_a_id)->where('isClose','N')->first();
        if(isset($data->id))
        {
            if($data->isImg == 'Y')
            {
                $ret = ($data->wp_work_img_id)? SHCSLib::toImgBase64String('permit',$data->wp_work_img_id,$img_resize) : '';
            } elseif ($data->wp_work_check_topic_id > 0) {
                $ret = wp_work_check_topic_a::getData($data->wp_work_check_topic_id,$img_resize);
            } else {
                $ret = $data->ans_value;
            }
            $created_at = $data->created_at;
            $charger_id = $data->mod_user;
        }
//        if($permit_topic_a_id == 120)dd($ret);
        return [$ret,$created_at,$charger_id];
    }
    //取得 簽核時間
    protected  function getTopicAnsAt($work_id,$permit_topic_a_id)
    {
        $ret = '';
        if(!$work_id || !$permit_topic_a_id) return $ret;
        $data = wp_work_topic_a::where('wp_work_id',$work_id)->where('wp_permit_topic_a_id',$permit_topic_a_id)->where('isClose','N')->first();

        return (isset($data->id))? $data->created_at : '';
    }
    //取得 簽核人員
    protected  function getTopicAnsUser($work_id,$permit_topic_a_id)
    {
        $ret = 0;
        if(!$work_id || !$permit_topic_a_id) return $ret;
        $data = wp_work_topic_a::where('wp_work_id',$work_id)->where('wp_permit_topic_a_id',$permit_topic_a_id)->where('isClose','N')->first();

        return (isset($data->mod_user))? $data->mod_user : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_work_topic_a::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
