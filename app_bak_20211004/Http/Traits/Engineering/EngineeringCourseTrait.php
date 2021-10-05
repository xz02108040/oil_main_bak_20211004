<?php

namespace App\Http\Traits\Engineering;

use App\Lib\CheckLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_license_type;
use App\Model\Engineering\et_traning_m;
use App\Model\User;

/**
 * 工程案件_教育訓練
 *
 */
trait EngineeringCourseTrait
{
    /**
     * 新增 工程案件_教育訓練
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringCourse($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->et_course_id)) return $ret;

        $INS = new e_project_c();
        $INS->e_project_id   = $data->e_project_id;
        $INS->et_course_id   = $data->et_course_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工程案件_教育訓練
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringCourse($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_c::find($id);
        if(!isset($UPD->et_course_id)) return $ret;
        //證照
        if(isset($data->et_course_id) && is_numeric($data->et_course_id) && $data->et_course_id !== $UPD->et_course_id)
        {
            $isUp++;
            $UPD->et_course_id = $data->et_course_id;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
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
     * 取得 工程案件_教育訓練
     *
     * @return array
     */
    public function getApiEngineeringCourseList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $project    = e_project::getName($pid);
        //取第一層
        $data = e_project_c::
                join('e_project as p','e_project_c.e_project_id','=','p.id')->
                join('et_course as ec','e_project_c.et_course_id','=','ec.id')->
                select('e_project_c.*','ec.name','ec.course_code','p.name as project','p.b_supply_id')->
                where('e_project_c.e_project_id',$pid)->orderby('e_project_c.isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['e_project']      = $project;
                $data[$k]['pass_amt']       = et_traning_m::getCoursePassAmt($v->et_course_id,$v->b_supply_id);
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工程案件_教育訓練「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringCourse($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $ynAry = SHCSLib::getCode('YES');
        //取第一層
        $data = e_project_c::
                join('et_course as ec','e_project_c.et_course_id','=','ec.id')->
                select('e_project_c.*','ec.name','ec.course_code','ec.isDoorRule')->
                where('e_project_c.e_project_id',$pid)->orderby('e_project_c.isClose')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']              = $v->id;
                $tmp['name']            = $v->name;
                $tmp['course_code']     = $v->course_code;
                $tmp['isDoorRuleName']  = isset($ynAry[$v->isDoorRule])? $ynAry[$v->isDoorRule] : '';
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
