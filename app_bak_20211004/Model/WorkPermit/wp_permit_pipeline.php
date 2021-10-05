<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_pipeline extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_pipeline';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_pipeline::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_pipeline::where('name',$id);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //取得 名稱
    protected  function isText($id)
    {
        if(!$id) return false;

        $data = wp_permit_pipeline::where('id',$id)->where('isClose','N')->first();

        return (isset($data->isText) && $data->isText == 'Y')? true : false;
    }
    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_permit_pipeline::where('id',$id)->where('isClose','N')->first();
        return isset($data->name)? $data->name : 'N';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_pipeline::select('id','name')->where('isClose','N');
        $data   = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');
        foreach ($data as $key => $val)
        {
            $ret[$val->id]  = $val->name;
        }
        return $ret;
    }
}
