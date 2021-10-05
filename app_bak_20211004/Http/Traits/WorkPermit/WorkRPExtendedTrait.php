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
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證 延長工單申請單
 *
 */
trait WorkRPExtendedTrait
{
    /**
     * 新增 工作許可證工作項目
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkRPExtendedTrait($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        $now = date('Y-m-d H:i:s');

        $INS = new wp_work_rp_extension();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->e_project_id          = $data->e_project_id;
        $INS->b_supply_id           = $data->b_supply_id;
        $INS->work_date             = $data->work_date;
        $INS->eta_etime1            = $data->eta_etime1;
        $INS->eta_etime2            = $data->eta_etime2;
        $INS->charge_dept1          = $data->charge_dept1;
        $INS->charge_dept2          = $data->charge_dept2;
        $INS->apply_user            = $mod_user;
        $INS->apply_memo            = $data->apply_memo;
        $INS->apply_stamp           = $now;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證工作項目
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkRPExtendedTrait($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_rp_extension::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //預計延長時間
        if(isset($data->eta_etime2) && $data->eta_etime2 && $data->eta_etime2 !== $UPD->eta_etime2)
        {
            $isUp++;
            $UPD->eta_etime2 = $data->eta_etime2;
        }
        //審查進度
        if(isset($data->aproc) && ($data->aproc) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            //監造審查
            if($data->aproc == 'P' || ($UPD->aproc == 'A' && $data->aproc == 'C'))
            {
                $UPD->charge_user1  = $mod_user;
                $UPD->charge_stamp1 = $now;
                $UPD->charge_memo1  = $data->charge_memo;
            }
            //轄區審查
            if($data->aproc == 'O' || $UPD->aproc == 'P' && $data->aproc == 'C')
            {
                $UPD->charge_user2  = $mod_user;
                $UPD->charge_stamp2 = $now;
                $UPD->charge_memo2  = $data->charge_memo;

                if($data->aproc == 'O')
                {
                    $tmp = [];
                    $tmp['isApplyOvertime']         = 'Y';
                    $tmp['wp_work_rp_extension_id'] = $id;
                    $tmp['eta_time']             = $UPD->eta_etime2;
//                    dd($UPD->wp_work_id,$tmp);
                    if(!$this->setWorkPermitWorkOrder($UPD->wp_work_id,$tmp,$mod_user))
                    {
                        $isUp--;
                    }
                }
            }
            $UPD->aproc = $data->aproc;

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
     * 取得 工作許可證工作項目
     *
     * @return array
     */
    public function getApiWorkRPExtendedTraitList($supply_id,$project_id = 0,$charge_dept = 0,$work_id = 0,$aproc = '',$work_datee = '')
    {
        $ret = array();
        if(!$work_datee) $work_datee = date('Y-m-d');
        $shifAry    = wp_permit_shift::getSelect(0); //班別
        $storeAry   = b_factory::getSelect(0);
        $localAry   = b_factory_a::getSelect(0);
        $deviceAry  = b_factory_b::getSelect(0);
        $doorAry    = b_factory_d::getSelect(0);
        $aprocAry1  = SHCSLib::getCode('PERMIT_APROC',0,0);
        $aprocAry2  = SHCSLib::getCode('EXTENDED_APROC',0,0);
        //取第一層
        $data = wp_work_rp_extension::join('wp_work as w','w.id','=','wp_work_rp_extension.wp_work_id')->
            join('b_supply as s','s.id','=','wp_work_rp_extension.b_supply_id')->
            join('e_project as p','p.id','=','wp_work_rp_extension.e_project_id')->
            join('be_dept as d1','d1.id','=','wp_work_rp_extension.charge_dept1')->
            join('be_dept as d2','d2.id','=','wp_work_rp_extension.charge_dept2')->
            where('wp_work_rp_extension.isClose','N')->where('wp_work_rp_extension.work_date',$work_datee)->
            select('wp_work_rp_extension.*','s.name as supply','p.name as project','d1.name as dept1','d2.name as dept2',
            'w.permit_no','w.wp_permit_danger as danger','w.wp_permit_shift_id','w.b_factory_id','w.b_factory_a_id',
            'w.b_factory_d_id','w.b_factory_b_id','w.b_factory_memo','w.wp_permit_workitem_memo','w.aproc as work_aproc');
        if($supply_id)
        {
            $data = $data->where('wp_work_rp_extension.b_supply_id',$supply_id);
        }
        if($project_id)
        {
            $data = $data->where('wp_work_rp_extension.e_project_id',$project_id);
        }
        if($charge_dept)
        {
            $data = $data->where('wp_work_rp_extension.charge_dept1',$charge_dept);
//            $data = $data->where(function ($query) use ($charge_dept) {
//                $query->where('wp_work_rp_extension.charge_dept1', '=', $charge_dept)
//                    ->orWhere('wp_work_rp_extension.charge_dept2', '=', $charge_dept);
//            });
        }
        if($work_id)
        {
            $data = $data->where('wp_work_rp_extension.wp_work_id',$work_id);
        }
        if($aproc)
        {
            $data = $data->where('wp_work_rp_extension.aproc',$aproc);
        }

        if($data->count())
        {
            $data = $data->orderby('wp_work_rp_extension.aproc')->get();
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


                list($user,$tel) = User::getMobileInfo($v->apply_user);
                $data[$k]['apply_user_name']    = $user;
                $data[$k]['apply_user_tel']     = $tel;
                $data[$k]['apply_stamp']        = substr($v->apply_stamp,0,19);
                list($user,$tel) = User::getMobileInfo($v->charge_user1);
                $data[$k]['charge_user_name1']    = $user;
                $data[$k]['charge_user_tel1']     = $tel;
                $data[$k]['charge_stamp1']        = substr($v->charge_stamp1,0,19);
                list($user,$tel) = User::getMobileInfo($v->charge_user2);
                $data[$k]['charge_user_name2']    = $user;
                $data[$k]['charge_user_tel2']     = $tel;
                $data[$k]['charge_stamp2']        = substr($v->charge_stamp2,0,19);

                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
