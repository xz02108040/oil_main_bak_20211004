<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_shift extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_shift';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_permit_shift::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($name,$extid = 0)
    {
        if(!$name) return 0;
        $data = wp_permit_shift::where('name',$name);
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
        $data = wp_permit_shift::find($id);
        return isset($data->id)? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1, $extAry = [], $isApp = 0)
    {
        $ret    = [];
        $data   = wp_permit_shift::select('id','name')->where('isClose','N');
        if(is_array($extAry) && count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data = $data->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            if($isApp)
            {
                $tmp = [];
                $tmp['id']      = $val->id;
                $tmp['name']    = $val->name;
                $ret[] = $tmp;
            } else {
                $ret[$val->id] = $val->name;
            }
        }

        return $ret;
    }
}
