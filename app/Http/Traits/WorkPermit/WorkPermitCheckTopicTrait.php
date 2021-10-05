<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_kind;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_check_topic_a;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use Illuminate\Support\Facades\Lang;

/**
 * 工作許可證_施工單_檢點單_紀錄
 *
 */
trait WorkPermitCheckTopicTrait
{
    use WorkPermitWorkerTrait;

    /**
     * 新增 工作許可證_施工單_檢點單_紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitCheckTopic($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;

        $INS = new wp_work_check_topic();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_list_id       = $data->wp_work_list_id;
        $INS->wp_check_id           = $data->wp_check_id;
        $INS->wp_check_topic_id     = $data->wp_check_topic_id;
        $INS->wp_work_topic_a_id    = $data->wp_work_topic_a_id;
        $INS->wp_work_process_id    = $data->wp_work_process_id;
        $INS->record_stamp          = $data->record_stamp;
        $INS->record_user           = $mod_user;


        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
//        dd([$INS,$data->check_ans]);
        $ret = ($INS->save())? $INS->id : 0;
        if($ret)
        {
            $wp_work_check_topic_id = $INS->id;
            //寫入工作許可證-檢點單題目內容
            if(count($data->check_ans))
            {
                foreach ($data->check_ans as $val)
                {
                    if(isset($val['check_topic_a_id']))
                    {
                        $option = wp_check_topic_a::genData($val['check_topic_a_id'],$data,$val['ans'],$mod_user);
                        $tmp = [];
                        $tmp['wp_work_id']              = $data->wp_work_id;
                        $tmp['wp_work_list_id']         = $data->wp_work_list_id;
                        $tmp['wp_check_id']             = $data->wp_check_id;
                        $tmp['wp_check_topic_id']       = $data->wp_check_topic_id;
                        $tmp['wp_check_topic_a_id']     = $val['check_topic_a_id'];
                        $tmp['wp_work_check_topic_id']  = $wp_work_check_topic_id;
                        $tmp['ans_value']               = isset($option['ans_value'])? $option['ans_value'] : '';
                        $tmp['isImg']                   = isset($option['isImg'])? $option['isImg'] : 'N';
                        $tmp['isGPS']                   = isset($option['isGPS'])? $option['isGPS'] : 'N';
                        $tmp['GPSX']                    = isset($option['GPSX'])? $option['GPSX'] : '';
                        $tmp['GPSY']                    = isset($option['GPSY'])? $option['GPSY'] : '';
                        $tmp['wp_work_img_id']          = isset($option['wp_work_img_id'])? $option['wp_work_img_id'] : 0;
//                        if($val['check_topic_a_id'] == 36) dd($tmp);
                        $this->createWorkPermitCheckTopicOption($tmp,$mod_user);
                    }
                }
                //２０１９－１０－１７
                //2.新增 氣體偵測紀錄表
                $main_record_kind = sys_param::getParam('PERMIT_CHECK_RECORD1_MAIN');
                $main_record_kind_Ary = explode(',',$main_record_kind);
                if(in_array($data->wp_check_id,$main_record_kind_Ary))
                {
                    wp_work_check_topic::genCheckTopicRecordAns($data->wp_work_process_id);
                }
            }
        }

        return $ret;
    }

    /**
     * 修改 工作許可證_施工單_檢點單_紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitCheckTopic($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_check_topic::find($id);
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
     * 取得 工作許可證_施工單_檢點單_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitCheckTopicList($work_topic_id )
    {
        $ret = array();
        //取第一層
        $data = wp_work_check_topic::where('wp_work_topic_id',$work_topic_id)->
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
     * 取得 工作許可證_施工單_檢點單_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitCheckTopic($work_id,$work_check_topic_id ,$appkey = '')
    {
        $topicAry = array();
        //取第一層
        $data = wp_work_check_topic::where('id',$work_check_topic_id)->where('isClose','N')->first();
        //dd([$work_check_topic_id,$data]);
        if(isset($data->id))
        {
            $check_id       = $data->wp_check_id;
            $topicAry       = $this->getApiWorkCheckTopic($check_id);
            $ansAry         = $this->getApiWorkPermitCheckTopicOption($work_id,$check_id,$work_check_topic_id,$appkey);
//            if($data->id == 28536)dd([$work_id,$work_check_topic_id,$check_id,$topicAry,$ansAry]);
            if(count($topicAry))
            {
                foreach ($topicAry as $key => $val)
                {
                    if(count($val['option']))
                    {
                        foreach ($val['option'] as $key2 => $val2)
                        {
                            if(isset($ansAry[$val2['check_topic_a_id']]))
                            {
                                //dd([$val2['topic_a_id'],$ansAry[$val2['topic_a_id']],$ansAry]);
                                $topicAry[$key]['option'][$key2]['ans_value']   = $ansAry[$val2['check_topic_a_id']];
                            } else if ($val2['check_topic_a_id'] == 234) { // 堆高機操作人員操作訓練合格證書字號
                                //取得工作許可證_工作人員證書字號
                                $workerLicenseCodes = $this->getApiWorkPermitWorkerLicenseCodes($work_id, 57);
                                $topicAry[$key]['option'][$key2]['ans_value']   = isset($workerLicenseCodes[22]) && !empty($workerLicenseCodes[22]) ? Lang::get('sys_supply.supply_125') . '：' . $workerLicenseCodes[22] : '';
                            } else if (in_array($val2['check_topic_a_id'], [148, 150, 204, 206])) {
                                $workerNames = $this->getApiWorkPermitWorkerNames($work_id);
                                switch ($val2['check_topic_a_id']) {
                                    case 148: // 起重機操作人員
                                        $topicAry[$key]['option'][$key2]['ans_value']   = isset($workerNames[4]) && !empty($workerNames[4]) ? $workerNames[4] : '';
                                        break;
                                    case 150: // 吊掛作業人員
                                        $topicAry[$key]['option'][$key2]['ans_value']   = isset($workerNames[5]) && !empty($workerNames[5]) ? $workerNames[5] : '';
                                        break;
                                    case 204: // 擋土支撐作業主管
                                        $topicAry[$key]['option'][$key2]['ans_value']   = isset($workerNames[14]) && !empty($workerNames[14]) ? $workerNames[14] : '';
                                        break;
                                    case 206: // 施工架組配作業主管
                                        $topicAry[$key]['option'][$key2]['ans_value']   = isset($workerNames[6]) && !empty($workerNames[6]) ? $workerNames[6] : '';
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
//        dd($topicAry[0]['option']);
        return $topicAry;
    }
}
