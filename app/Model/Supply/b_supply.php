<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = b_supply::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($name,$extid = 0)
    {
        if(!$name) return 0;
        $data = b_supply::where('name',$name);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    //統編是否存在
    protected  function isTaxExist($tax,$extid = 0)
    {
        if(!$tax) return 0;
        $data = b_supply::where('tax_num',$tax);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    //簡稱是否存在
    protected  function isSubNameExist($sub_name,$extid = 0)
    {
        if(!$sub_name) return 0;
        $data = b_supply::where('sub_name',$sub_name);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    //搜尋 名稱
    protected  function SearchName($name)
    {
        if(!$name) return 0;
        $data = b_supply::where('name','like','%'.$name.'%')->where('isClose','N')->select('id')->first();
        return isset($data->id)? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = b_supply::find($id);
        return isset($data->id)? $data->name : '';
    }

    //取得 統編
    protected  function getTaxNum($id)
    {
        if(!$id) return 0;
        $data = b_supply::find($id);
        return isset($data->id)? $data->tax_num : 0;
    }

    //取得 名稱
    protected  function getSubName($id)
    {
        if(!$id) return '';
        $data = b_supply::find($id);
        return isset($data->id)? $data->sub_name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret    = [];
        $data   = b_supply::select('id','name')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getSelect2($isFirst = 1)
    {
        $ret    = [];
        $data   = b_supply::select('id','sub_name','name')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = ($val->sub_name)? $val->sub_name : $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($b_supply_id = 0)
    {
        $ret    = [];
        $data   = b_supply::join('e_project as p','p.b_supply_id','=','b_supply.id')->
        select('b_supply.id','b_supply.name')->where('b_supply.isClose','N')->
        whereIn('p.aproc',['B','R','P'])->where('p.isClose','N')->groupBy('b_supply.id','b_supply.name');
        if($b_supply_id)
        {
            $data = $data->where('b_supply.id',$b_supply_id);
        }

        foreach ($data->get() as $val)
        {
            $tmp = [];
            $tmp['id']      = $val->id;
            $tmp['name']    = $val->name;
            $ret[]          = $tmp;
        }

        return $ret;
    }
}
