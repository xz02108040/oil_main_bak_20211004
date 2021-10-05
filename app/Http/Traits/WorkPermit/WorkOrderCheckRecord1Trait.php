<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work_check_record1;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_process;

/**
 * 工作許可證_施工單_氣體偵測紀錄表
 *
 */
trait WorkOrderCheckRecord1Trait
{
    /**
     * 新增 工作許可證_施工單_檢點單_選項紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkOrderCheckRecord1($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_check_record1();
        $INS->wp_work_id                    = $data->wp_work_id;
        $INS->wp_work_list_id               = $data->wp_work_list_id;
        $INS->wp_work_process_id            = $data->wp_work_process_id;
        $INS->wp_check_id                   = $data->wp_check_id;
        $INS->wp_check_topic_a_id1          = $data->wp_check_topic_a_id1;
        $INS->wp_check_topic_a_id2          = $data->wp_check_topic_a_id2;
        $INS->wp_check_topic_a_id3          = $data->wp_check_topic_a_id3;
        $INS->wp_check_topic_a_id4          = $data->wp_check_topic_a_id4;
        $INS->wp_check_topic_a_id5          = isset($data->wp_check_topic_a_id5)? $data->wp_check_topic_a_id5 : '';
        $INS->wp_work_check_topic_a_id1     = $data->wp_work_check_topic_a_id1;
        $INS->wp_work_check_topic_a_id2     = $data->wp_work_check_topic_a_id2;
        $INS->wp_work_check_topic_a_id3     = $data->wp_work_check_topic_a_id3;
        $INS->wp_work_check_topic_a_id4     = $data->wp_work_check_topic_a_id4;
        $INS->wp_work_check_topic_a_id5     = isset($data->wp_work_check_topic_a_id5)? $data->wp_work_check_topic_a_id5 : '';
        $INS->record_stamp                  = $data->record_stamp;
        $INS->record_user                   = $data->record_user;
        $INS->record1                       = isset($data->record1)? $data->record1 : '';
        $INS->isOver1                       = (isset($data->isOver1) && $data->isOver1)? 'Y' : 'N';
        $INS->record2                       = isset($data->record2)? $data->record2 : '';
        $INS->isOver2                       = (isset($data->isOver2) && $data->isOver2)? 'Y' : 'N';
        $INS->record3                       = isset($data->record3)? $data->record3 : '';
        $INS->isOver3                       = (isset($data->isOver3) && $data->isOver3)? 'Y' : 'N';
        $INS->record4                       = isset($data->record4)? $data->record4 : '';
        $INS->isOver4                       = (isset($data->isOver4) && $data->isOver4)? 'Y' : 'N';
        $INS->record5                       = isset($data->record5)? $data->record5 : '';
        $INS->isOver5                       = (isset($data->isOver5) && $data->isOver5)? 'Y' : 'N';
        $INS->record5n                      = isset($data->record5n)? $data->record5n : '';
        $INS->wp_work_img_id                = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;

        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_施工單_檢點單_選項紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkOrderCheckRecord1($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_check_topic_a::find($id);
        if(!isset($UPD->ans_value)) return $ret;
        //答案
        if(isset($data->ans_value) && $data->ans_value && $data->ans_value !== $UPD->ans_value)
        {
            $isUp++;
            $UPD->ans_value         = $data->ans_value;
            $UPD->isImg             = isset($data->isImg)? $data->isImg : 'N';
            $UPD->isGPS             = isset($data->isGPS)? $data->isGPS : 'N';
            $UPD->GPSX              = isset($data->GPSX)? $data->GPSX : 0;
            $UPD->GPSY              = isset($data->GPSY)? $data->GPSY : 0;
            $UPD->wp_work_img_id    = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;
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
     * 取得 工作許可證_施工單_檢點單_選項紀錄
     *
     * @return array
     */
    public function getApiWorkOrderCheckRecord1List($work_topic_id )
    {
        $ret = array();
        //取第一層
        $data = wp_work_check_topic_a::where('wp_work_topic_id',$work_topic_id)->
                where('isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_施工單_檢點單_選項紀錄
     *
     * @return array
     */
    public function getApiWorkOrderCheckRecord1($work_check_topic_id ,$appkey = '')
    {
        $ret = array();
        //取第一層
        $data = wp_work_check_topic_a::where('wp_work_check_topic_id',$work_check_topic_id)->where('isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                //IMG 圖片
                if($v->wp_work_img_id)
                {
                    $ans_val = url('img/Permit').'/'.SHCSLib::encode($v->wp_work_img_id).'?key='.$appkey;
                }
                else {
                    $unit = (strlen($v->ans_value))? wp_check_topic_a::getUnit($v->wp_check_topic_a_id) : '';
                    $ans_val = $v->ans_value . $unit;
                }
                $ret[$v->wp_check_topic_a_id] = $ans_val;
            }
        }

        return $ret;
    }

}
