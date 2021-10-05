<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_check extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_check';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id,$iid)
    {
        if(!$id) return 0;
        $data = wp_work_check::where('wp_work_id',$id)->where('wp_check_kind_id',$iid)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }


    //取得 下拉選擇全部
    protected  function getSelect($wid, $isFirst = 1, $isApi = 0)
    {
        $ret    = [];
        $data   = wp_work_check::where('wp_work_id',$wid)->select('id','wp_check_kind_id')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi)
            {
                $tmp = [];
                $tmp['id']      = $val->wp_check_kind_id;
                $tmp['name']    = wp_check_kind::getName($val->wp_check_kind_id);
                $ret[] = $tmp;
            } else {
                $ret[$val->wp_check_kind_id] = wp_check_kind::getName($val->wp_check_kind_id);
            }
        }

        return $ret;
    }
}
