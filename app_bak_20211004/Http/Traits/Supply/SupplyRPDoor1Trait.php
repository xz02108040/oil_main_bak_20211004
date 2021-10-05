<?php

namespace App\Http\Traits\Supply;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_car;
use App\Model\Factory\b_car_type;
use App\Model\Supply\b_supply_rp_door1;
use App\Model\Supply\b_supply_rp_door1_a;
use App\Model\View\view_door_car;
use Illuminate\Database\Eloquent\Model;
use Storage;
use App\Model\User;

/**
 * 承攬商[臨時入場/過夜] 申請單
 *
 */
trait SupplyRPDoor1Trait
{
    /**
     * 新增 承攬商[臨時入場/過夜] 申請單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSupplyRPDoor1($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_supply_id) || !isset($data->member)) return $ret;

        $INS = new b_supply_rp_door1();
        $INS->b_supply_id       = $data->b_supply_id;
        $INS->apply_door_kind   = $data->apply_door_kind;
        $INS->b_factory_id      = $data->b_factory_id;
        $INS->sdate             = $data->sdate;
        $INS->edate             = $data->edate;
        $INS->stime             = $data->stime;
        $INS->etime             = $data->etime;
        $INS->e_project_id      = $data->e_project_id;
        $INS->project_aproc     = $data->project_aproc;
        $INS->project_edate     = $data->project_edate;
        $INS->charge_dept       = $data->charge_dept;
        $INS->apply_user        = $mod_user;
        $INS->apply_stamp       = date('Y-m-d H:i:s');
        $INS->apply_memo        = $data->apply_memo;
        $INS->aproc             = "O"; //管理者新增 直接通過

        $INS->charge_user        = $mod_user;
        $INS->charge_stamp       = date('Y-m-d H:i:s');
        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;

        $INS->work_place        = $data->work_place;
        $INS->sdate_allow       = $data->sdate;
        $INS->edate_allow       = $data->edate;
        $INS->stime_allow       = $data->stime;
        $INS->etime_allow       = $data->etime;

        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
           foreach ($data->member as $val)
           {
               $tmp = [];
               $tmp['b_supply_rp_door1_id'] = $ret;
               $tmp['b_car_id']             = 0;
               $tmp['b_cust_id']            = 0;
               $tmp['job_kind']             = 0;
               $tmp['cpc_tag']              = '';
               $jobkindAry = SHCSLib::getCode('JOB_KIND',0);
               if($INS->apply_door_kind == 2)
               {
                    $tmp['b_car_id']             = $val;
                    $tmp['job_kind']             = view_door_car::getDoorDateRange($INS->e_project_id,$val);
               } else {
                    list($job_kind,$cpc_tag) = e_project_s::getJobListSupply($INS->e_project_id,$val);
                    $tmp['b_cust_id']            = $val;
                    $tmp['job_kind']             = isset($jobkindAry[$job_kind])? $jobkindAry[$job_kind] : '';
                    $tmp['cpc_tag']              = $cpc_tag;
               }
               $this->createSupplyRPDoor1Detail($tmp,$mod_user);
           }
            if($INS->apply_door_kind == 3){
                if($data->car && count($data->car)){
                    foreach ($data->car as $val)
                    {
                        $tmp = [];
                        $tmp['b_supply_rp_door1_id'] = $ret;
                        $tmp['b_car_id']             = 0;
                        $tmp['b_cust_id']            = 0;
                        $tmp['job_kind']             = 0;
                        $tmp['cpc_tag']              = '';

                        $tmp['b_car_id']             = $val;
                        $tmp['job_kind']             = view_door_car::getDoorDateRange($INS->e_project_id,$val);
                        $this->createSupplyRPDoor1Detail($tmp,$mod_user);
                    }
                }
            }
        }

        return $ret;
    }
    /**
     * 修改 承攬商[臨時入場/過夜] 申請單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSupplyRPDoor1($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_supply_rp_door1::find($id);
        if(!isset($UPD->b_supply_id)) return $ret;
        //申請廠區
        if(isset($data->b_factory_id) && $data->b_factory_id && $data->b_factory_id !==  $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id  = $data->b_factory_id;
        }
        //開始日
        if(isset($data->sdate) && $data->sdate && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate  = $data->sdate;
        }
        //結束日
        if(isset($data->edate) && $data->edate && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate  = $data->edate;
        }

        //核准開始日
        if(isset($data->sdate_allow) && $data->sdate_allow && $data->sdate_allow !==  $UPD->sdate_allow)
        {
            $isUp++;
            $UPD->sdate_allow  = $data->sdate_allow;
        }
        //核准開始時間
        if(isset($data->stime_allow)  && $data->stime_allow && $data->stime_allow !==  $UPD->stime_allow)
        {
            $isUp++;
            $UPD->stime_allow  = $data->stime_allow;
        }
        //核准結束日
        if(isset($data->edate_allow)  && $data->edate_allow && $data->edate_allow !==  $UPD->edate_allow)
        {
            $UPD->edate_allow  = $data->edate_allow;
        }
        //核准結束時間
        if(isset($data->etime_allow)  && $data->etime_allow && $data->etime_allow !==  $UPD->etime_allow)
        {
            $UPD->etime_allow  = $data->etime_allow;
        }

        //MEMO
        if(isset($data->apply_memo) && $data->apply_memo && $data->apply_memo !==  $UPD->apply_memo)
        {
            $isUp++;
            $UPD->apply_memo  = $data->apply_memo;
        }
        //APROC
        if(isset($data->aproc) && $data->aproc && $data->aproc !==  $UPD->aproc)
        {
            $isUp++;
            list($project_aproc,$project_edate) = e_project::getProjectList1($UPD->e_project_id);
            $UPD->project_aproc = $project_aproc;
            $UPD->project_edate = $project_edate;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_user   = $mod_user;
            $UPD->charge_memo   = $data->charge_memo;
            $UPD->charge_stamp  = $now;
        }
        //人車
        if(isset($data->member) && count($data->member))
        {
            $memberAry = $this->getApiSupplyRPDoor1DetailList($data->id);
            if(count($memberAry) != count($data->member))
            {
                foreach ($data->member as $val)
                {
                    foreach ($memberAry as $key2 => $val2)
                    {
                        if($val2['id'] == $val)
                        {
                            unset($memberAry[$key2]);
                        }
                    }
                }
                foreach ($memberAry as $val3)
                {
                    $tmp = [];
                    $tmp['isClose'] = 'Y';
                    if($this->setSupplyRPDoor1Detail($val3['id'],$tmp,$mod_user))
                    {
                        $isUp++;
                    }
                }
            }
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
     * 取得 承攬商[臨時入場/過夜] 申請單
     *
     * @return array
     */
    public function getApiRPDoor1List($projectAry = [],$kind = 0, $aproc = '', $isNow = '', $sdate = '', $edate = '')
    {
        $ret = array();
        $typeAry  = SHCSLib::getCode('RP_DOOR_KIND1');
        $aprocAry = SHCSLib::getCode('RP_DOOR_APROC1');
        $today    = date('Y-m-d');
        //取第一層
        $data = b_supply_rp_door1::
        join('b_supply as s','s.id','=','b_supply_rp_door1.b_supply_id')->
        join('e_project as p','p.id','=','b_supply_rp_door1.e_project_id')->
        join('b_factory as f','f.id','=','b_supply_rp_door1.b_factory_id')->
        join('be_dept as d','d.id','=','b_supply_rp_door1.charge_dept')->
        where('b_supply_rp_door1.isClose','N')->
        select('b_supply_rp_door1.*','p.name as project','p.project_no','s.name as supply','f.name as store','d.name as charge_dept_name');

        if($projectAry)
        {
            $data = $data->whereIn('b_supply_rp_door1.e_project_id',$projectAry);
        }
        if($kind)
        {
            $data = $data->where('b_supply_rp_door1.apply_door_kind',$kind);
        }
        if($aproc)
        {
            $data = $data->where('b_supply_rp_door1.aproc',$aproc);
        }
        if($isNow == 'Y')
        {
            $data = $data->where('b_supply_rp_door1.edate','>=',$today);
        }
        if($isNow == 'N')
        {
            $data = $data->where('b_supply_rp_door1.edate','<',$today);
        }

        if($sdate == '' && $edate != ''){
            $sdate = $edate;
        }
        if($edate == '' && $sdate != ''){
            $edate = $sdate;
        }
        if($sdate){
            $data = $data->where('b_supply_rp_door1.edate','>=',$sdate);
        }
        if($edate){
            $data = $data->where('b_supply_rp_door1.sdate','<=',$edate);
        }
        $data = $data->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['apply_door_kind_name']   = isset($typeAry[$v->apply_door_kind])? $typeAry[$v->apply_door_kind] : '';
                $data[$k]['aproc_name']             = isset($aprocAry[$v->aproc])? $aprocAry[$v->aproc] : '';
                $data[$k]['apply_amt']              = b_supply_rp_door1_a::getAmt($v->id);
                $data[$k]['isActive']               = (strtotime($v->edate) < strtotime($today))? 'N' : 'Y';
                $data[$k]['apply_user']             = User::getName($v->apply_user);
                $data[$k]['charge_user']            = User::getName($v->charge_user);
                $data[$k]['new_user']               = User::getName($v->new_user);
                $data[$k]['mod_user']               = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
