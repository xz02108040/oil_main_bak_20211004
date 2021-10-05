<?php

namespace App\Http\Traits\Engineering;

use App\Model\User;
use App\Lib\SHCSLib;
use App\Lib\CheckLib;
use App\Model\Engineering\e_license;
use App\Model\Engineering\e_project;
use App\Http\Traits\Factory\CarTrait;
use App\Model\Engineering\e_project_c;
use App\Model\Engineering\e_project_d;
use App\Model\Engineering\e_project_l;
use App\Model\Engineering\e_project_car;
use App\Model\Engineering\e_license_type;

/**
 * 工程案件_車輛
 *
 */
trait EngineeringCarTrait
{
    use CarTrait;
    
    /**
     * 新增 工程案件_車輛
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createEngineeringCar($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->e_project_id) || !isset($data->b_car_id)) return $ret;

        $INS = new e_project_car();
        $INS->e_project_id  = $data->e_project_id;
        $INS->b_supply_id   = $data->b_supply_id;
        $INS->b_car_id      = $data->b_car_id;
        $INS->door_sdate    = $data->door_sdate;
        $INS->door_edate    = $data->door_edate;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工程案件_車輛
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setEngineeringCar($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_project_car::find($id);
        if(!isset($UPD->b_car_id)) return $ret;
        //car
        if(isset($data->b_car_id) && is_numeric($data->b_car_id) && $data->b_car_id !== $UPD->b_car_id)
        {
            $isUp++;
            $UPD->b_car_id = $data->b_car_id;
        }
        //門禁可進場日
        if(isset($data->door_sdate) && CheckLib::isDate($data->door_sdate) && $data->door_sdate !== $UPD->door_sdate)
        {
            $isUp++;
            $UPD->door_sdate = $data->door_sdate;
        }
        //門禁進場有效日
        if(isset($data->door_edate) && CheckLib::isDate($data->door_edate) && $data->door_edate !== $UPD->door_edate)
        {
            $isUp++;
            $UPD->door_edate = $data->door_edate;
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

                // 車輛退案件時，同步清除通行證號
                $upAry2 = array();
                $upAry2['car_memo'] = ' ';
                $this->setCar($UPD->b_car_id, $upAry2, $mod_user);
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
     * 取得 工程案件_車輛
     *
     * @return array
     */
    public function getApiEngineeringCarList($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        $project    = e_project::getName($pid);
        //取第一層
        $data = e_project_car::
                join('e_project as p','e_project_car.e_project_id','=','p.id')->
                join('b_car as ec','e_project_car.b_car_id','=','ec.id')->
                join('b_car_type as ct','ec.car_type','=','ct.id')->
                join('b_supply as s','e_project_car.b_supply_id','=','s.id')->
                select('e_project_car.*','ec.car_no','ec.car_memo','s.name as supply','ct.name as car_type_name','p.name as project','p.edate as project_edate')->
                where('e_project_car.e_project_id',$pid)->
                where('e_project_car.isClose','N');
//        dd($data,$pid);
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $data[$k]['project']        = $project;
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工程案件_車輛「ＡＰＰ」
     *
     * @return array
     */
    public function getApiEngineeringCar($pid = 0)
    {
        $ret = array();
        if(!$pid) return $ret;
        //取第一層
        $data = e_project_car::
        join('b_car as ec','e_project_car.b_car_id','=','ec.id')->
        join('b_car_type as ct','ec.car_type','=','ct.id')->
        join('b_supply as s','e_project_car.b_supply_id','=','s.id')->
        select('e_project_car.b_car_id','e_project_car.door_sdate','e_project_car.door_edate','ec.car_no','s.name as supply','ct.name as car_type_name')->
        where('e_project_car.e_project_id',$pid)->
        where('e_project_car.isClose','N');

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['id']              = $v->b_car_id;
                $tmp['name']            = $v->car_no;
                $tmp['type']            = $v->car_type_name;
                $tmp['sdate']           = $v->door_sdate;
                $tmp['edate']           = $v->door_edate;
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

}
