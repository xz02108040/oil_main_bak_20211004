<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_topic_type extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_topic_type';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = wp_topic_type::find($id);
        return (isset($data->id))? $data->id : 0;
    }
    //名稱是否存在
    protected  function isNameExist($id,$extid = 0)
    {
        if(!$id) return 0;
        $data = wp_topic_type::where('name',$id);
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
        return wp_topic_type::find($id)->name;
    }

    //取得 下拉選擇全部
    protected  function getSelect($showType = 1, $isFirst = 1,$extAry = [], $isPermit = '', $isCheck = '')
    {
        $ret    = [];
        $data   = wp_topic_type::select('id','name','isOption')->where('isClose','N');
        if(is_array($extAry) && count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        if($isPermit)
        {
            $data = $data->where('isPermit',$isPermit);
        }
        if($isCheck)
        {
            $data = $data->where('isCheck',$isCheck);
        }
        $data = $data->orderby('show_order')->get();
        $ret[0] = ($isFirst)? Lang::get('sys_base.base_10015') : '';

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = ($showType == 2)? $val->isOption : $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($extAry = [],$isFirst = 1)
    {
        $ret    = [];
        $data   = wp_topic_type::select('id','name','isOption')->where('isClose','N');
        if(is_array($extAry) && count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data = $data->orderby('show_order')->get();

        if($isFirst) $ret[] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id']       = $val->id;
            $tmp['name']     = $val->name;
            $tmp['isOption'] = $val->isOption;
            $ret[] = $tmp;
        }

        return $ret;
    }
}
