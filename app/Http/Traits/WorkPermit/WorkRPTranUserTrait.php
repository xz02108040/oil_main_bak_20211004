<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_danger;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_pipeline;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_permit_workitem;
use App\Model\WorkPermit\wp_work_rp_extension;
use App\Model\WorkPermit\wp_work_rp_tranuser;
use App\Model\WorkPermit\wp_work_rp_tranuser_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證 轉單工單申請單
 *
 */
trait WorkRPTranUserTrait
{
    /**
     * 新增 轉單工單申請單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkRPTranUserTrait($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        $now = date('Y-m-d H:i:s');

        $INS = new wp_work_rp_tranuser();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_id2           = $data->wp_work_id2;
        $INS->e_project_id          = $data->e_project_id;
        $INS->b_supply_id           = $data->b_supply_id;
        $INS->work_date             = $data->work_date;
        $INS->charge_dept1          = $data->charge_dept1;
        $INS->apply_user            = $mod_user;
        $INS->apply_memo            = $data->apply_memo;
        $INS->apply_stamp           = $now;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret && isset($data->tran_user))
        {
            foreach ($data->tran_user as $val)
            {
                if(isset($val->b_cust_id))
                {
                    $tmp = [];
                    $tmp['wp_work_rp_tranuser_id']  = $ret;
                    $tmp['wp_work_id']              = $data->wp_work_id;
                    $tmp['b_cust_id']               = $val->b_cust_id;
                    $this->createWorkRPTranUserDetailTrait($tmp,$mod_user);
                }

            }
        }

        return $ret;
    }

    /**
     * 修改 轉單工單申請單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkRPTranUserTrait($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_rp_tranuser::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //審查進度
        if(isset($data->aproc) && ($data->aproc) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc = $data->aproc;
            //監造審查
            if($data->aproc == 'O')
            {
                $UPD->charge_user1  = $mod_user;
                $UPD->charge_stamp1 = $now;
                $UPD->charge_memo1  = $data->charge_memo;

                //釋放該人員
                $userAry = wp_work_rp_tranuser_a::getAry($id);
                if(count($userAry))
                {
                    $freeUserAry = [];
                    foreach ($userAry as $val)
                    {
                        $b_cust_id = isset($val['user_id'])? $val['user_id'] : 0;
//                        dd($val,$b_cust_id);
                        if($b_cust_id)
                        {
                            $freeUserAry[]                  = $b_cust_id;
                            $INS = $val;
                            $INS['wp_work_id']              = $UPD->wp_work_id2;
                            $INS['engineering_identity_id'] = 9;
                            $INS['apply_type']              = 3;
                            $INS['isGuest']                 = 'N';
                            $INS['aproc']                   = 'R';
                            $this->createWorkPermitWorker($INS,$mod_user);
                        }
                    }
                    if(is_array($freeUserAry) && count($freeUserAry)){
                        $this->chgWorkPermitWorkerMenInOut($UPD->wp_work_id,$UPD->wp_work_id2,$freeUserAry,$mod_user);
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
     * 取得 轉單工單申請單
     *
     * @return array
     */
    public function getApiWorkRPTranUserTraitList($supply_id,$project_id = 0,$charge_dept = 0,$work_id = 0,$work_id2 = 0,$aproc = '',$work_datee = '')
    {
        $ret = array();
        if(!$work_datee) $work_datee = date('Y-m-d');
        $shifAry    = wp_permit_shift::getSelect(0); //班別
        $storeAry   = b_factory::getSelect(0);
        $localAry   = b_factory_a::getSelect(0);
        $deviceAry  = b_factory_b::getSelect(0);
        $doorAry    = b_factory_d::getSelect(0);
        $aprocAry1  = SHCSLib::getCode('PERMIT_APROC',0,0);
        $aprocAry2  = SHCSLib::getCode('TRANUSER_APROC',0,0);
        //取第一層
        $data = wp_work_rp_tranuser::join('wp_work as w','w.id','=','wp_work_rp_tranuser.wp_work_id')->
            join('wp_work as w2','w2.id','=','wp_work_rp_tranuser.wp_work_id2')->
            join('b_supply as s','s.id','=','wp_work_rp_tranuser.b_supply_id')->
            join('e_project as p','p.id','=','wp_work_rp_tranuser.e_project_id')->
            join('be_dept as d1','d1.id','=','wp_work_rp_tranuser.charge_dept1')->
            where('wp_work_rp_tranuser.isClose','N')->where('wp_work_rp_tranuser.work_date',$work_datee)->
            select('wp_work_rp_tranuser.*','s.name as supply','p.name as project','d1.name as dept1',
            'w.permit_no','w.wp_permit_danger as danger','w.wp_permit_shift_id','w.b_factory_id','w.b_factory_a_id',
            'w.b_factory_d_id','w.b_factory_b_id','w.b_factory_memo','w.wp_permit_workitem_memo','w.aproc as work_aproc',
            'w2.permit_no as permit_no2','w2.wp_permit_danger as danger2','w2.wp_permit_shift_id as wp_permit_shift_id2',
            'w2.b_factory_id as b_factory_id2','w2.b_factory_a_id as b_factory_a_id2','w.b_car_memo as b_car_memo',
            'w2.b_factory_d_id as b_factory_d_id2','w2.b_factory_b_id as b_factory_b_id2','w2.b_car_memo as b_car_memo2',
            'w2.b_factory_memo as b_factory_memo2','w2.wp_permit_workitem_memo as wp_permit_workitem_memo2');
        if($supply_id)
        {
            $data = $data->where('wp_work_rp_tranuser.b_supply_id',$supply_id);
        }
        if($project_id)
        {
            $data = $data->where('wp_work_rp_tranuser.e_project_id',$project_id);
        }
        if($charge_dept)
        {
            $data = $data->where(function ($query) use ($charge_dept) {
                $query->where('wp_work_rp_tranuser.charge_dept1', '=', $charge_dept);
            });
        }
        if($work_id)
        {
            $data = $data->where('wp_work_rp_tranuser.wp_work_id',$work_id);
        }
        if($work_id2)
        {
            $data = $data->where('wp_work_rp_tranuser.wp_work_id2',$work_id2);
        }
        if($aproc)
        {
            $data = $data->where('wp_work_rp_tranuser.aproc',$aproc);
        }

        if($data->count())
        {
            $data = $data->orderby('wp_work_rp_tranuser.aproc')->get();
            foreach ($data as $k => $v)
            {
                $data[$k]['shift_name']         = isset($shifAry[$v->wp_permit_shift_id])?  $shifAry[$v->wp_permit_shift_id] : '';
                $data[$k]['store']              = isset($storeAry[$v->b_factory_id])?       $storeAry[$v->b_factory_id] : '';
                $data[$k]['local']              = isset($localAry[$v->b_factory_a_id])?     $localAry[$v->b_factory_a_id] : '';
                $data[$k]['device']             = isset($deviceAry[$v->b_factory_b_id])?    $deviceAry[$v->b_factory_b_id] : '';
                $data[$k]['door']               = isset($doorAry[$v->b_factory_d_id])?      $doorAry[$v->b_factory_d_id] : '';
                $data[$k]['work_aproc']         = isset($aprocAry1[$v->work_aproc])?        $aprocAry1[$v->work_aproc] : '';
                $data[$k]['aproc_name']         = isset($aprocAry2[$v->aproc])?             $aprocAry2[$v->aproc] : '';

                $data[$k]['supply_worker']      = wp_work_worker::getSelect($v->wp_work_id,1,0,1);

                $data[$k]['supply_safer']       = wp_work_worker::getSelect($v->wp_work_id,2,0,1);

                $data[$k]['shift_name2']        = isset($shifAry[$v->wp_permit_shift_id2])?  $shifAry[$v->wp_permit_shift_id2] : '';
                $data[$k]['store2']             = isset($storeAry[$v->b_factory_id2])?       $storeAry[$v->b_factory_id2] : '';
                $data[$k]['local2']             = isset($localAry[$v->b_factory_a_id2])?     $localAry[$v->b_factory_a_id2] : '';
                $data[$k]['device2']            = isset($deviceAry[$v->b_factory_b_id2])?    $deviceAry[$v->b_factory_b_id2] : '';
                $data[$k]['door2']              = isset($doorAry[$v->b_factory_d_id2])?      $doorAry[$v->b_factory_d_id2] : '';

                $data[$k]['supply_worker2']      = wp_work_worker::getSelect($v->wp_work_id2,1,0,1);

                $data[$k]['supply_safer2']       = wp_work_worker::getSelect($v->wp_work_id2,2,0,1);

                list($user,$tel) = User::getMobileInfo($v->apply_user);
                $data[$k]['apply_user_name']    = $user;
                $data[$k]['apply_user_tel']     = $tel;
                $data[$k]['apply_stamp']        = substr($v->apply_stamp,0,19);
                list($user,$tel) = User::getMobileInfo($v->charge_user1);
                $data[$k]['charge_user_name1']    = $user;
                $data[$k]['charge_user_tel1']     = $tel;
                $data[$k]['charge_stamp1']        = substr($v->charge_stamp1,0,19);


                $data[$k]['tran_user']   = $this->getApiWorkRPTranUserDetailTrait($v->id);

                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
