<?php

namespace App\Model\Engineering;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;
use DB;

class et_traning_time extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'et_traning_time';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = et_traning_time::where('id',$id);
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getWeek($id)
    {
        if(!$id) return 0;
        $data  = et_traning_time::where('id',$id)->select('week')->first();
        return isset($data->week)? $data->week : 0;
    }

    /**
     *  星期名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        $ret = '';
        if(!$id) return $ret;
        $weekAry = SHCSLib::getCode('WEEK');
        $data    = et_traning_time::where('id',$id)->select('week','stime','etime')->first();
        if(isset($data->week))
        {
            $ret    = isset($weekAry[$data->week])? $weekAry[$data->week] : '';
            // $ret   .= '( '.substr($data->stime,0,5).' - '.substr($data->etime,0,5).')';
        }
        return $ret;
    }

    /**
     *  課程時段名稱
     * @param $id
     * @return int
     */
    protected function getTimeName($id)
    {
        $ret = '';
        if(!$id) return $ret;
        $weekAry = SHCSLib::getCode('WEEK');
        $data    = et_traning_time::where('id',$id)->select('week','stime','etime')->first();
        if(isset($data->week))
        {
            $ret    = isset($weekAry[$data->week])? $weekAry[$data->week] : '';
            $ret   .= '( '.substr($data->stime,0,5).' - '.substr($data->etime,0,5).')';
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($course_id,$isFirst = 1)
    {
        $ret  = [];
        $weekAry = SHCSLib::getCode('WEEK');
        $sWhere='WEEK';
        $data = et_traning_time::where('et_traning_time.et_course_id',$course_id)
        ->join('sys_code as sc','week','sc.status_key')
        ->select('et_traning_time.id','week','stime','etime',DB::raw('ROW_NUMBER() OVER(ORDER BY show_order,stime) AS ROW'))
        ->where('et_traning_time.isClose','N')->where('status_code',$sWhere);
        $data = $data->orderby('ROW','asc');

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $name = isset($weekAry[$val->week])? $weekAry[$val->week] : '';
                $name.= '( '.substr($val->stime,0,5).' - '.substr($val->etime,0,5).')';
                $ret[' ' . $val->id] = $name;
            }
        }


        return $ret;
    }



}
