<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\et_course;
use App\Model\Engineering\et_traning;
use App\Model\Engineering\et_traning_m;
use App\Model\Engineering\et_traning_time;
use App\Model\User;

/**
 * 教育訓練開課
 *
 */
trait TraningTrait
{
    /**
     * 新增 教育訓練開課
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createCyclicTraning($data,$mod_user = 1)
    {
        $isSuc = $isErr = $isExist = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->et_course_id) || !isset($data->week)|| !isset($data->sdate)|| !isset($data->edate)) return [0,0,0];
        $circleWeekAry = SHCSLib::get_circle_week($data->sdate,$data->edate,$data->week);

        if(count($circleWeekAry))
        {
            foreach ($circleWeekAry as $traningDate)
            {
                $isTraningExist = et_traning::isExist($data->et_course_id,$traningDate,$traningDate,$data->et_traning_time_id);
//                dd($circleWeekAry,$traningDate,$isTraningExist);
                if(!$isTraningExist)
                {
                    $INS = [];
                    $INS['et_course_id']        = $data->et_course_id;
                    $INS['course_no']           = str_replace('-','',$traningDate);
                    $INS['teacher']             = $data->teacher;
                    $INS['sdate']               = $traningDate;
                    $INS['edate']               = $traningDate;
                    $INS['et_traning_time_id']  = $data->et_traning_time_id;
                    $INS['register_day']        = SHCSLib::addDay('-'.$data->register_day,$traningDate);
                    $INS['register_time']       = $data->register_time;
                    $INS['register_men']        = $data->register_men;
                    $INS['memo']                = $data->memo;
                    $ret = $this->createTraning($INS,$mod_user);
                    if($ret) $isSuc++;
                    if(!$ret) $isErr++;
                } else {
                    $isExist++;
                }
            }
        }

        return [$isSuc,$isErr,$isExist];
    }

    /**
     * 新增 教育訓練開課
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createTraning($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->et_course_id)) return $ret;

        $INS = new et_traning();
        $INS->et_course_id          = $data->et_course_id;
        $INS->course_no             = $data->course_no;
        $INS->teacher               = $data->teacher ? $data->teacher : '';
        $INS->sdate                 = $data->sdate ? $data->sdate : date('Y-m-d');
        $INS->edate                 = $data->edate ? $data->edate : '9999-12-31';
        $INS->et_traning_time_id    = $data->et_traning_time_id ? $data->et_traning_time_id : 0;
        $INS->register_men_limit    = $data->register_men ? $data->register_men : 0;
        $INS->register_day_limit    = $data->register_day ? $data->register_day : 1;
        $INS->register_time_limit   = $data->register_time ? $data->register_time : '12:00';
        $INS->memo                  = $data->memo ? $data->memo : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 教育訓練開課
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setTraning($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = et_traning::find($id);
        if(!isset($UPD->et_course_id)) return $ret;
        //課程ＩＤ
        if(isset($data->et_course_id) && is_numeric($data->et_course_id) && $data->et_course_id !==  $UPD->et_course_id)
        {
            $isUp++;
            $UPD->et_course_id = $data->et_course_id;
        }
        //開課代碼
        if(isset($data->course_no) && strlen($data->course_no) && $data->course_no !==  $UPD->course_no)
        {
            $isUp++;
            $UPD->course_no = $data->course_no;
        }
        //授課教師
        if(isset($data->teacher) && strlen($data->teacher) && $data->teacher !==  $UPD->teacher)
        {
            $isUp++;
            $UPD->teacher = $data->teacher;
        }
        //開始時間
        if(isset($data->sdate) && CheckLib::isDate($data->sdate) && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //結束時間
        if(isset($data->edate) && CheckLib::isDate($data->edate) && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //
        if(isset($data->register_day) && CheckLib::isDate($data->register_day) && $data->register_day !==  $UPD->register_day)
        {
            $isUp++;
            $UPD->register_day_limit = $data->register_day;
        }

        //說明
        if(isset($data->register_time) && strlen($data->register_time) && $data->register_time !==  $UPD->register_time)
        {
            $isUp++;
            $UPD->register_time_limit = $data->register_time;
        }
        //說明
        if(isset($data->register_men) && $data->register_men !==  $UPD->register_men)
        {
            $isUp++;
            $UPD->register_men_limit = $data->register_men;
        }
        //說明
        if(isset($data->memo) && strlen($data->memo) && $data->memo !==  $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 教育訓練開課
     *
     * @return array
     */
    public function getApiTraningCourseList($teacher = '', $sdate = '',$edate = '',$isClose = 'N')
    {
        $ret = array();
        $courseAry = et_course::getSelect();
        if(!$edate) $edate = date('Y-m-d');
        if(!$sdate) $sdate = $edate;
        //取第一層
        $data = et_traning::where('isClose',$isClose);
        if($teacher)
        {
            $data = $data->where('teacher','like','%'.$teacher.'%');
        }
        if($sdate)
        {
            $data = $data->where('sdate','<=',$sdate);
        }
        if($edate)
        {
            $data = $data->where('edate','>=',$edate);
        }
        $data = $data->select('et_course_id')->groupby('et_course_id')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['course']      = isset($courseAry[$v->et_course_id])? $courseAry[$v->et_course_id] : '';
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 教育訓練開課
     *
     * @return array
     */
    public function getApiTraningList($course_id = 0, $teacher = '', $sdate = '',$edate = '',$isClose = 'N')
    {
        $ret = array();
        $courseAry = et_course::getSelect();
        //取第一層
        $data = et_traning::where('isClose',$isClose);
        if($course_id)
        {
            $data = $data->where('et_course_id',$course_id);
        }
        if($teacher)
        {
            $data = $data->where('teacher','like','%'.$teacher.'%');
        }
        if($sdate)
        {
            $data = $data->where('sdate','>=',$sdate);
        }
        if($edate)
        {
            $data = $data->where('edate','<=',$edate);
        }
        $data = $data->orderby('sdate')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['course']      = isset($courseAry[$v->et_course_id])? $courseAry[$v->et_course_id] : '';
                $data[$k]['week']        = et_traning_time::getName($v->et_traning_time_id);
                $data[$k]['traning_time']= et_traning_time::getTimeName($v->et_traning_time_id);
                $data[$k]['traning_men1']= et_traning_m::getAmt($v->id,0,['A']);
                $data[$k]['traning_men2']= et_traning_m::getAmt($v->id,0,['P']);
                $data[$k]['traning_men3']= et_traning_m::getAmt($v->id,0,['R']);
                $data[$k]['traning_men4']= et_traning_m::getAmt($v->id,0,['O']);
                $data[$k]['traning_men5']= et_traning_m::getAmt($v->id,0,['B']);
                $data[$k]['traning_men6']= et_traning_m::getAmt($v->id,0,['C']);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
