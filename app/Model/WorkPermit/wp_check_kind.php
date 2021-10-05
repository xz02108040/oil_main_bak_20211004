<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_check_kind extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_check_kind';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_check_kind::where('id',$id)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = wp_check_kind::find($id);
        return (isset($data->id))? $data->name : '';
    }


    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1, $isApi = 0, $extAry = [])
    {
        $ret    = [];
        $extAry = (!is_array($extAry) && $extAry)? [$extAry] : $extAry;
        $data   = wp_check_kind::where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data   = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi)
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
