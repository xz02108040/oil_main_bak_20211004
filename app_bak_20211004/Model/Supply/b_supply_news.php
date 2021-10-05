<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_news extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_news';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = b_supply_news::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($name,$extid = 0)
    {
        if(!$name) return 0;
        $data = b_supply_news::where('name',$name);
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
        $data = b_supply_news::where('tax_num',$tax);
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
        $data = b_supply_news::where('sub_name',$sub_name);
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
        $data = b_supply_news::find($id);
        return isset($data->id)? $data->name : '';
    }

    //取得 統編
    protected  function getTaxNum($id)
    {
        if(!$id) return 0;
        $data = b_supply_news::find($id);
        return isset($data->id)? $data->tax_num : 0;
    }

    //取得 名稱
    protected  function getSubName($id)
    {
        if(!$id) return '';
        $data = b_supply_news::find($id);
        return isset($data->id)? $data->sub_name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect()
    {
        $ret    = [];
        $data   = b_supply_news::select('id','name')->where('isClose','N')->get();
        $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getSelect2()
    {
        $ret    = [];
        $data   = b_supply_news::select('id','sub_name','name')->where('isClose','N')->get();
        $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = ($val->sub_name)? $val->sub_name : $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($supply_id,$sdate = '',$edate = '')
    {
        $today  = date('Y-m-d');
        $typeAry= SHCSLib::getCode('NEWS_TYPE',0);
        if(!$sdate) $sdate = $today;
        if(!$edate) $edate = $today;
        $ret    = [];
        $data   = b_supply_news::where('b_supply_id',$supply_id)->where('sdate','<=',$sdate)->
                    where('edate','>=',$edate);

        if($data->count())
        {
            foreach ($data->get() as $key => $val)
            {
                $tmp = [];
                $tmp['title']       = $val->news_title;
                $tmp['sub_title']   = isset($typeAry[$val->news_type])? $typeAry[$val->news_type] :'';
                $tmp['memo']        = $val->new_body;
                $tmp['id']          = $val->id;
                $tmp['stamp']       = substr($val->created_at,0,16);
                $ret[]              = $tmp;
            }
        }


        return $ret;
    }
}
