<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_workitem extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_workitem';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_workitem::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_permit_workitem::where('name',$id);
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

        $data = wp_permit_workitem::where('id',$id)->where('isClose','N')->first();

        return (isset($data->isText) && $data->isText == 'Y')? true : false;
    }
    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_permit_workitem::where('id',$id)->where('isClose','N')->first();
        return isset($data->name)? $data->name : 'N';
    }

    //取得 名稱
    protected  function getKind($id)
    {
        if(!$id) return '';
        $data = wp_permit_workitem::find($id);

        return isset($data->id)? $data->wp_permit_kind_id : 0;
    }

    //取得 下拉選擇全部
    protected  function getSelect($kid = 0, $isFirst = 1,$isShowKind = 1)
    {
        $ret    = [];
        $kindAry= wp_permit_kind::getSelect();
        $data   = wp_permit_workitem::select('id','wp_permit_kind_id','name')->where('isClose','N');
        if($kid)
        {
            $data = $data->where('wp_permit_kind_id',$kid);
        }
        $data   = $data->orderby('wp_permit_kind_id')->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $kind           = isset($kindAry[$val->wp_permit_kind_id])? ($kindAry[$val->wp_permit_kind_id].'-') : '';
            $ret[$val->id]  = ($isShowKind)? $kind.$val->name : $val->name;
        }
        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getReptSelect($kind)
    {
        $ret    = [];
        $data   = wp_permit_workitem::select('id','name')->where('isClose','N');
        $data   = $data->where('wp_permit_kind_id',$kind)->orderby('show_order')->get();

        foreach ($data as $val)
        {
            $tmp = [];
            $tmp['name']    = $val->name;
            $tmp['amt']     = 0;
            $ret[$val->id] = $tmp;
        }
        return $ret;
    }
}
