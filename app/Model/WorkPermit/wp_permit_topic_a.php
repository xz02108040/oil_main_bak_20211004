<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_topic_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_topic_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic_a::where('id',$id)->where('isClose','N')->first();
        return (isset($data->id))? $data->id : 0;
    }

    //名稱是否存在
    protected  function isNameExist($id,$name, $extid = 0)
    {
        if(!$id || !$name) return 0;
        $data = wp_permit_topic_a::where('wp_permit_topic_id',$id)->where('name',$name);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //是否為 圖片格式
    protected  function isImgAns($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic_a::find($id);
        $option_type = (isset($data->id))? $data->wp_option_type : 0;

        return (in_array($option_type,[7]))? 1 : 0;
    }
    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_permit_topic_a::find($id);
        return (isset($data->id))? $data->name : '';
    }
    //取得 該題目共作答幾題
    protected  function getAnsAmt($topic_id)
    {
        if(!$topic_id) return 0;
        return wp_permit_topic_a::where('wp_permit_topic_id',$topic_id)->where('isAns','Y')->where('isClose','N')->count();
    }
    //取得 名稱
    protected  function getTopicIdList($id)
    {
        if(!$id) return [0,0];
        $data = wp_permit_topic_a::find($id);
        return (isset($data->id))? [$data->wp_permit_topic_id,$data->wp_option_type] : [0,0];
    }

    //取得 名稱
    protected  function getIdentity($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic_a::where('id',$id)->where('isClose','N')->first();
        return (isset($data->id))? $data->engineering_identity_id : 0;
    }

    //取得 題目類型
    protected  function getType($id)
    {
        if(!$id) return 0;
        $data = wp_permit_topic_a::find($id);
        return (isset($data->id))? $data->wp_option_type : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_topic_a::select('id','name')->where('isClose','N')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
