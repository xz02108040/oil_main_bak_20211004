<?php

namespace App\Model\Factory;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_car_type extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_car_type';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($name,$extid = 0)
    {
        if(!$name) return 0;
        $data = b_car_type::where('name',$name);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = b_car_type::find($id);
        return (isset($data->b_cust_id))? $data->name : '';
    }

    //取得 動力模式
    protected  function getOilKind($id)
    {
        if(!$id) return '';
        $data = b_car_type::find($id);
        return (isset($data->oil_kind))? $data->oil_kind : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = b_car_type::select('id','name')->where('isClose','N');
        $data   = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
