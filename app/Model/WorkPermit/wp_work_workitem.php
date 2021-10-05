<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_workitem extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_workitem';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id,$iid)
    {
        if(!$id) return 0;
        $data = wp_work_workitem::where('wp_work_id',$id)->where('wp_permit_workitem_id',$iid)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    protected function getKind($work_id)
    {
        $ret = [];
        $data   = wp_work_workitem::where('wp_work_id',$work_id)->
                    select('wp_permit_kind_id')->where('isClose','N')->groupby('wp_permit_kind_id');
        if($data->count())
        {
            $data = $data->orderby('wp_permit_kind_id')->get();
            foreach ($data as $val)
            {
                $ret[$val->wp_permit_kind_id] = wp_permit_kind::getSubName($val->wp_permit_kind_id);
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($wid)
    {
        $wp_permit_workitem = wp_permit_workitem::getSelect(0,0,0);
        $ret    = [];
        $data   = wp_work_workitem::where('wp_work_id',$wid)->
                select('id','wp_permit_workitem_id','memo')->where('isClose','N')->get();

        foreach ($data as $key => $val)
        {

            $workitemName = (in_array($val->wp_permit_workitem_id,[1,10,17]))? $val->memo : (isset($wp_permit_workitem[$val->wp_permit_workitem_id])? $wp_permit_workitem[$val->wp_permit_workitem_id] : '');
            $ret[$val->wp_permit_workitem_id] = $workitemName;
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getApiAllSelect($wid)
    {
        $wp_permit_kind     = wp_permit_kind::getSelect(0);
        $wp_permit_workitem = wp_permit_workitem::getSelect(0,0,0);
        $ret    = [];
        $data   = wp_work_workitem::where('wp_work_id',$wid)->
                select('id','wp_permit_workitem_id','wp_permit_kind_id')->where('isClose','N')->get();

        foreach ($data as $key => $val)
        {
            $kid = $val->wp_permit_kind_id;
            $tmp = [];
            $tmp['id']   = $val->wp_permit_workitem_id;
            $tmp['name'] = isset($wp_permit_workitem[$val->wp_permit_workitem_id])? $wp_permit_workitem[$val->wp_permit_workitem_id] : '';

            if(!isset($ret[$kid]))
            {
                $ret[$kid]          = [];
                $ret[$kid]['id']    = $kid;
                $ret[$kid]['name']  = isset($wp_permit_kind[$kid])? $wp_permit_kind[$kid] : '';
            }
            $ret[$kid]['item'][] = $tmp;
        }

        return $ret;
    }
}
