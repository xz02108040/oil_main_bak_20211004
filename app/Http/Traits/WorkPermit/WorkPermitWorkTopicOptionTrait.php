<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;

/**
 * 工作許可證_施工單_題目_選項紀錄
 *
 */
trait WorkPermitWorkTopicOptionTrait
{
    /**
     * 新增 工作許可證_施工單_題目_選項紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkTopicOption($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        if($wp_work_topic_a_id = wp_work_topic_a::isExist($data->wp_work_id,$data->wp_work_list_id,$data->wp_permit_topic_a_id))
        {
            $ret = $this->setWorkPermitWorkTopicOption($wp_work_topic_a_id,$data,$mod_user);
        } else {
            $INS = new wp_work_topic_a();
            $INS->wp_work_id            = $data->wp_work_id;
            $INS->wp_work_list_id       = $data->wp_work_list_id;
            $INS->wp_permit_topic_id    = $data->wp_permit_topic_id;
            $INS->wp_permit_topic_a_id  = $data->wp_permit_topic_a_id;
            $INS->ans_value             = $data->ans_value;
            $INS->reject_memo           = isset($data->reject_memo)? $data->reject_memo : '';
            $INS->isImg                 = isset($data->isImg)? $data->isImg : 'N';
            $INS->isLostImg             = isset($data->isLostImg)? $data->isLostImg : 'N';
            $INS->isGPS                 = isset($data->isGPS)? $data->isGPS : 'N';
            $INS->GPSX                  = isset($data->GPSX)? $data->GPSX : 0;
            $INS->GPSY                  = isset($data->GPSY)? $data->GPSY : 0;
            $INS->wp_work_img_id        = isset($data->wp_work_img_id)? $data->wp_work_img_id : 0;

            $INS->new_user      = $mod_user;
            $INS->mod_user      = $mod_user;
            $ret = ($INS->save())? $INS->id : 0;
            $wp_work_topic_a_id = ($ret)? $ret : 0;
        }
        //if($data->wp_check_id) dd([$data]);
        if($ret)
        {
            //選項答案
            $data->wp_work_topic_a_id = $wp_work_topic_a_id;
            $wp_work_process_topic_id = $this->createWorkPermitWorkProcessTopicOption($data,$mod_user);
            //if($data->wp_check_id) dd([$data,$data->check_value]);
            //紀錄檢點單
            if($data->wp_check_id && isset($data->check_value) && count($data->check_value))
            {
                //檢核表－檢核項目
                foreach ($data->check_value as $val)
                {
                    $data->wp_check_topic_id = $val['check_topic_id'];
                    $data->record_stamp      = isset($val['record_stamp'])? $val['record_stamp'] : date('Y-m-d H:i:s');
                    $data->check_ans         = isset($val['option'])? $val['option'] : [];
//                    dd($val,$data);
                    if($check_topic_id =$this->createWorkPermitCheckTopic($data,$mod_user))
                    {
                        $UPD1 = wp_work_topic_a::find($wp_work_topic_a_id);
                        $UPD1->ans_value             = $check_topic_id;
                        $UPD1->wp_work_check_topic_id= $check_topic_id;
                        $UPD1->save();

                        $UPD2 = wp_work_process_topic::find($wp_work_process_topic_id);
                        $UPD2->ans_value             = $check_topic_id;
                        $UPD2->wp_work_check_topic_id= $check_topic_id;
                        $UPD2->save();
                    }
                }



            }
        }
        return $ret;
    }

    /**
     * 修改 工作許可證_施工單_題目_選項紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkTopicOption($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_topic_a::find($id);
        if(!isset($UPD->ans_value)) return $ret;
        //答案
        if(isset($data->ans_value) && $data->ans_value && $data->ans_value !== $UPD->ans_value)
        {
            $isUp++;
            $UPD->ans_value         = $data->ans_value;
            $UPD->reject_memo       = isset($data->reject_memo)? $data->reject_memo : '';
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
     * 取得 工作許可證_施工單_題目_選項紀錄
     *
     * @return array
     */
    public function getLostImgTopic( $work_id ,$permit_process_id)
    {
        $ret = [];
        //一般題目
        $needCheckTopicAry = $this->getProcessTopicOptionID($permit_process_id,[],7);
        //取第一層
        $data = wp_work_topic_a::where('wp_work_id',$work_id)->whereIn('wp_permit_topic_a_id',$needCheckTopicAry)->
                where('isClose','N')->where('isLostImg','Y')->where('wp_work_img_id',0);
        $ret1  = $data->count();
        if($ret1)
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $tmp = [];
                $tmp['wp_work_topic_a_id']      = $v->id;
                $tmp['wp_permit_topic_a_id']    = $v->wp_permit_topic_a_id;
                $tmp['wp_work_check_topic_a_id']= 0;
                $tmp['name']                    = wp_permit_topic_a::getName($v->wp_permit_topic_a_id);
                $ret[] = $tmp;
            }
        }

        //檢點單
        $needCheckTopicAry = $this->getProcessTopicOptionID($permit_process_id,[],9);
        //取第一層
        $data = wp_work_topic_a::where('wp_work_id',$work_id)->whereIn('wp_permit_topic_a_id',$needCheckTopicAry)->
                where('isClose','N')->where('isLostImg','Y');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $wp_work_check_topic_id      = $v->wp_work_check_topic_id;
                if($wp_work_check_topic_id)
                {
                    $data2 = wp_work_check_topic_a::where('wp_work_id',$work_id)->where('wp_work_check_topic_id',$wp_work_check_topic_id)->
                            where('isClose','N')->where('isLostImg','Y')->where('wp_work_img_id',0);
                    $ret2  = $data2->count();
                    if($ret2)
                    {
                        $data2 = $data2->get();
                        foreach ($data2 as $k2 => $v2)
                        {
                            $tmp = [];
                            $tmp['wp_work_topic_a_id']      = $v->id;
                            $tmp['wp_permit_topic_a_id']    = $v->wp_permit_topic_a_id;
                            $tmp['wp_check_topic_a_id']     = $v2->id;
                            $tmp['name']                    = wp_check_topic_a::getName($v2->wp_check_topic_a_id);
                            $ret[] = $tmp;
                        }
                    }
                }
            }
        }

        return $ret;
    }

}
