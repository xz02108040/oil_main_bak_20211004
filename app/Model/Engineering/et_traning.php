<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class et_traning extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'et_traning';
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
    protected function isExist($course,$sdate,$edate,$et_traning_time_id,$extid = 0)
    {
        if(!$course) return 0;
        $data  = et_traning::where('et_course_id',$course)->where('isClose','N')->
        where('sdate', $sdate)->where('edate', $edate)->where('et_traning_time_id', $et_traning_time_id);
//        where(function ($query) use ($sdate,$edate) {
//            $query->where(function ($query) use ($sdate) {
//                $query->where('sdate', '<=', $sdate)
//                    ->where('edate', '>=', $sdate);
//            })->orWhere(function ($query) use ($edate) {
//                $query->where('sdate', '<=', $edate)
//                    ->where('edate', '>=', $edate);
//            });
//        });
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isCourseNoExist($course_no,$extid = 0)
    {
        if(!$course_no) return 0;
        $data  = et_traning::where('course_no',$course_no)->where('isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isRegisterWithInDate($id)
    {
        if(!$id) return 0;
        $today = date('Y-m-d');

        $data  = et_traning::where('id',$id)->where('register_day_limit','>=',$today)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isTraningWithInDate($id)
    {
        if(!$id) return 0;
        $today = date('Y-m-d');

        $data  = et_traning::where('id',$id)->where('sdate','>=',$today)->where('isClose','N');
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = et_traning::where('id',$id)->select('course_no','sdate','edate')->first();
        return isset($data->course_no)? (($data->sdate == $data->edate)? $data->sdate : $data->sdate.' - '.$data->edate) : '';
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getCourseInfo($id)
    {
        if(!$id) return [0,'',''];
        $data  = et_traning::where('id',$id)->select('et_course_id','sdate','edate')->first();
        return isset($data->sdate)? [$data->et_course_id,$data->sdate,$data->edate] : [0,'',''];
    }

    /**
     *  課程ＩＤ
     * @param $id
     * @return int
     */
    protected function getCourseID($id)
    {
        if(!$id) return 0;
        $data  = et_traning::where('id',$id)->select('et_course_id')->first();
        return isset($data->et_course_id)? $data->et_course_id : 0;
    }

    /**
     *  課程名稱
     * @param $id
     * @return string
     */
    protected function getCourseName($id)
    {
        if(!$id) return 0;
        $data  = et_traning::where('et_traning.id',$id)->join('et_course as a','et_traning.et_course_id','a.id')->select('a.name')->first();
        return isset($data->name)? $data->name : '';
    }

    /**
     *  開課日期
     * @param $id
     * @return string
     */
    protected function getTranDate($id)
    {
        if(!$id) return 0;
        $data  = et_traning::find($id);
        if($data->sdate == $data->edate){
            $result = $data->sdate;
        }else{
            $result = $data->sdate . '~' . $data->edate;
        }
        return $result;
    }

    /**
     *  上課時段
     * @param $id
     * @return string
     */
    protected function getTranTime($id)
    {
        if(!$id) return 0;
        $data  = et_traning::find($id);
        return isset($data->id)? et_traning_time::getTimeName($data->et_traning_time_id) : '';
    }

    /**
     *  課程ＩＤ
     * @param $id
     * @return int
     */
    protected function getForeverTraning($course_id)
    {
        if(!$course_id) return 0;
        $data  = et_traning::where('et_course_id',$course_id)->where('edate','9999-12-31')->where('isClose','N')->select('id')->first();
        return isset($data->id)? $data->id : 0;
    }




    //取得 下拉選擇全部
    protected  function getSelect($course_id, $isFirst = 1)
    {
        $ret  = [];
        $data = et_traning::select('id','course_no','sdate','edate')->where('isClose','N');
        if($course_id)
        {
            $data = $data->where('et_course_id',$course_id);
        }

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        if($data->count())
        {
            foreach ($data->get() as $key => $val)
            {
                $ret[$val->id] = ($val->sdate == $val->edate)? $val->edate : $val->sdate.' - '.$val->edate;
            }
        }


        return $ret;
    }



}
