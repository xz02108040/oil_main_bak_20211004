<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;

/**
 * 工作許可證_施工單_題目_選項紀錄
 *
 */
trait WorkPermitWorkProcessTopicOption
{
    /**
     * 新增 工作許可證_施工單_題目_選項紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkProcessTopicOption($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_process_topic();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_list_id       = $data->wp_work_list_id;
        $INS->wp_work_process_id    = $data->wp_work_process_id;
        $INS->wp_permit_topic_a_id  = $data->wp_permit_topic_a_id;
        $INS->wp_work_topic_a_id    = $data->wp_work_topic_a_id;
        $INS->ans_value             = $data->ans_value;
        $INS->reject_memo           = isset($data->reject_memo)? $data->reject_memo : '';
        $INS->isImg                 = isset($data->isImg)? $data->isImg : 'N';
        $INS->isGPS                 = isset($data->isGPS)? $data->isGPS : 'N';
        $INS->GPSX                  = isset($data->GPSX)? $data->GPSX : 0;
        $INS->GPSY                  = isset($data->GPSY)? $data->GPSY : 0;
        $INS->wp_work_img_id        = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;
        $INS->wp_work_check_topic_id= isset($data->wp_work_check_topic_id)? $data->wp_work_check_topic_id : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        //回寫 圖片記錄檔
        if($ret && $INS->wp_work_img_id)
        {
            $data->wp_work_process_topic_id = $ret;
            $this->setWorkPermitWorkImg($INS->wp_work_img_id,$data,$mod_user);
        }
        return $ret;
    }



    /**
     * 取得 工作許可證_施工單_題目_選項紀錄
     *
     * @return array
     */
    public function getApiWorkPermitWorkTopicOption($wp_work_process_id)
    {
        $ret = array();
        //取第一層
        $data = wp_work_process_topic::where('wp_work_process_id',$wp_work_process_id)->where('isClose','N')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['wp_permit_topic_a_id'] = $v->wp_permit_topic_a_id;
                $tmp['ans_value']            = $v->ans_value;
                $ret[$v->wp_permit_topic_a_id] = $tmp;
            }
        }

        return $ret;
    }
}
