<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_record1;
use App\Model\WorkPermit\wp_work_check_record2;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證_局限空間進出紀錄表
 *
 */
trait WorkOrderCheckRecord2Trait
{
    /**
     * 新增 工作許可證_局限空間進出紀錄表
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkOrderCheckRecord2Group($data,$mod_user = 1)
    {
        $suc = $isIn = 0;
        $now = SHCSLib::getNow();
        if(is_array($data)) $data = (object)$data;
        foreach ($data->user as $val)
        {
            $tmp = [];
            $tmp['wp_work_id']          = $data->wp_work_id;
            $tmp['wp_work_list_id']     = $data->wp_work_list_id;
            $tmp['wp_work_process_id']  = $data->wp_work_process_id;
            $tmp['wp_check_kind_id']    = $data->wp_check_kind_id;
            $tmp['b_cust_id']           = $val->user_id;
            $tmp['door_type']           = isset($val->door_type)? $val->door_type : 0;
            $tmp['door_stamp']          = isset($val->door_stamp)? $val->door_stamp : $now;
            if($tmp['b_cust_id'] && $tmp['door_type'] && $this->createWorkOrderCheckRecord2($tmp,$mod_user))
            {
                $suc++;
                if($tmp['door_type'] == 1) $isIn++;
            }
        }
        //
        if($isIn)
        {
            //voice_box
            list($local_id,$local,$local_ip,$supply_id,$tax_num) = wp_work::getLocalInfo($data->wp_work_id);//音訊盒位置
            LogLib::putPushVoiceBoxLog($local_id,$local_ip,$supply_id,$tax_num,1,'confined');
        }

        return $suc;
    }
    /**
     * 新增 工作許可證_局限空間進出紀錄表
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkOrderCheckRecord2($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id) || !isset($data->b_cust_id) || !isset($data->door_type)) return $ret;
        $now = SHCSLib::getNow();
        $id  = 0;
        if($data->door_type == 2)
        {
            //如果是離開，則找上一筆進場
            $id = wp_work_check_record2::isExist($data->wp_work_id,$data->wp_work_list_id,$data->wp_work_process_id,$data->wp_check_kind_id,
                $data->b_cust_id,1);
        }

        if($id)
        {
            $INS = wp_work_check_record2::find($id);
            $INS->err_code              = 0;
            $INS->door_type             = $data->door_type;
            $INS->door_stamp2           = isset($data->door_stamp)? $data->door_stamp : $now;
            $INS->record_user2          = $mod_user;
            $INS->GPSX2                 = isset($data->GPSX2)? $data->GPSX2 : '';
            $INS->GPSY2                 = isset($data->GPSY2)? $data->GPSY2 : '';
        } else {
            $INS = new wp_work_check_record2();
            $INS->wp_work_id            = $data->wp_work_id;
            $INS->wp_work_list_id       = $data->wp_work_list_id;
            $INS->wp_work_process_id    = $data->wp_work_process_id;
            $INS->wp_check_kind_id      = $data->wp_check_kind_id;
            $INS->b_cust_id             = $data->b_cust_id;

            if($data->door_type == 2)
            {
                $INS->err_code              = 2;
                $INS->door_stamp2           = isset($data->door_stamp)? $data->door_stamp : $now;
                $INS->GPSX2                 = isset($data->GPSX2)? $data->GPSX2 : '';
                $INS->GPSY2                 = isset($data->GPSY2)? $data->GPSY2 : '';
                $INS->record_user2          = $mod_user;
            } else {
                $INS->err_code              = 1;
                $INS->door_type             = $data->door_type;
                $INS->door_stamp1           = isset($data->door_stamp)? $data->door_stamp : $now;
                $INS->GPSX1                 = isset($data->GPSX1)? $data->GPSX1 : '';
                $INS->GPSY1                 = isset($data->GPSY1)? $data->GPSY1 : '';
                $INS->record_user1          = $mod_user;
            }
            $INS->new_user              = $mod_user;
        }
        $INS->mod_user              = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;


        return $ret;
    }

    /**
     * 修改 工作許可證_局限空間進出紀錄表
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkOrderCheckRecord2($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = SHCSLib::getNow();
        $isUp = 0;

        $UPD = wp_work_check_record2::find($id);
        if(!isset($UPD->id)) return $ret;
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
     * 工作許可證_局限空間進出紀錄表
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function getWorkOrderCheckRecord2List($work_id,$check_kind_id = 0)
    {
        $ret = [];
        if(!$work_id) return $ret;
        $doorTypeAry = SHCSLib::getCode('CHECK_RECORD2_DOORTYPE');
        $errAry      = SHCSLib::getCode('CHECK_RECORD2_ERR');

        $data = wp_work_check_record2::join('b_cust as u','u.id','=','wp_work_check_record2.b_cust_id')->
            where('wp_work_check_record2.wp_work_id',$work_id)->where('wp_work_check_record2.isClose','N');
        if($check_kind_id)
        {
            $data = $data->where('wp_work_check_record2.wp_check_kind_id',$check_kind_id);
        }
        $data = $data->orderby('wp_work_check_record2.b_cust_id')->select('wp_work_check_record2.*','u.name');
        if($data->count())
        {
            $memberAry = [];
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['b_cust_id']       = $val->b_cust_id;
                $tmp['name']            = $val->name;
                $tmp['door_type']       = isset($doorTypeAry[$val->door_type])? $doorTypeAry[$val->door_type] : '';
                $tmp['err_code']        = $val->err_code;
                $tmp['err_code_name']   = isset($errAry[$val->err_code])? $errAry[$val->err_code] : '';
                $tmp['door_stamp1']     = substr($val->door_stamp1,0,19);
                $tmp['record_user1']    = User::getName($val->record_user1);
                $tmp['GPSX1']           = $val->GPSX1;
                $tmp['GPSY1']           = $val->GPSY1;
                $tmp['door_stamp2']     = substr($val->door_stamp2,0,19);
                $tmp['record_user2']    = User::getName($val->record_user2);
                $tmp['GPSX2']           = $val->GPSX2;
                $tmp['GPSY2']           = $val->GPSY2;
                //$tmp['isOverTime']      = ($val->err_code == 1)? 'Y' : 'N' ;
                $memberAry[] = (object)$tmp;

            }
            $ret = (object)$memberAry;
        }

        return $ret;
    }

}
