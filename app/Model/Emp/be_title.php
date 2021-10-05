<?php

namespace App\Model\Emp;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class be_title extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'be_title';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = be_title::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = be_title::where('name',$id);
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
        return be_title::find($id)->name;
    }

    //取得 職等
    protected  function getLevel($id)
    {
        if(!$id) return '';
        return be_title::find($id)->level;
    }

    //取得 下拉選擇全部
    protected  function getSelect()
    {
        $ret    = [];
        $data   = be_title::orderby('show_order')->select('id','name')->where('isClose','N')->get();
        $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
