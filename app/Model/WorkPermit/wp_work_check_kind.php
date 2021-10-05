<?php

namespace App\Model\WorkPermit;

use App\Lib\FormLib;
use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_check_kind extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_check_kind';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($id,$wp_check_kind_idd)
    {
        if(!$id) return 0;
        $data = wp_work_check_kind::where('wp_work_id',$id)->where('wp_check_kind_id',$wp_check_kind_idd)->where('isClose','N');
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }


    //取得 下拉選擇全部
    protected  function getSelect($wid, $isFirst = 1, $isApi = 0)
    {
        $ret    = [];
        $data   = wp_work_check_kind::where('wp_work_id',$wid)->select('id','wp_check_kind_id')->where('isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($isApi == 1)
            {
                $tmp = [];
                $tmp['id']      = $val->wp_check_kind_id;
                $tmp['name']    = wp_check_kind::getName($val->wp_check_kind_id);
                $ret[] = $tmp;
            } elseif($isApi == 2) {
                $ret[$val->wp_check_kind_id] = $val->wp_check_kind_id;
            } else {
                $ret[$val->wp_check_kind_id] = wp_check_kind::getName($val->wp_check_kind_id);
            }
        }

        return $ret;
    }
    //取得 下拉選擇全部
    protected  function getButtom($wid,$showType = 1)
    {
        $ret      = '';
        $colorAry = [1=>1,2=>2];
        $data     = wp_work_check_kind::where('wp_work_id',$wid)->select('id','wp_check_kind_id')->where('isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                if($showType == 2 && $ret) $ret .= ',';
                $name = wp_check_kind::getName($val->wp_check_kind_id);
                $color= isset($colorAry[$val->wp_check_kind_id])?$colorAry[$val->wp_check_kind_id] : 5;
                $ret .= ($showType == 2)? $name : FormLib::linkbtn( '#',$name,$color);
            }
        }

        return $ret;
    }
    //取得 [列印報表使用]
    protected  function getCheckText($wid)
    {
        $retAry   = wp_check_kind::getCheckText();
        $data     = wp_work_check_kind::where('wp_work_id',$wid)->select('id','wp_check_kind_id')->where('isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                if(isset($retAry[$val->wp_check_kind_id]))
                {
                    $retAry[$val->wp_check_kind_id] = '☑';
                }
            }
        }

        return $retAry;
    }
}
