<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_process_topic;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_topic_type;
use App\Model\WorkPermit\wp_work_check;
use App\Model\WorkPermit\wp_work_worker;
use Illuminate\Support\Facades\Lang;

/**
 * 工作許可證_流程_題目
 *
 */
trait WorkPermitProcessTopicTrait
{
    /**
     * 新增 工作許可證_流程_題目 [批次]
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitProcessTopicGroup($data,$mod_user = 1)
    {
        $ret = false;
        $suc = $err = 0;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        foreach ($data->topic as $tid)
        {
            $UPD = [];
            $UPD['wp_permit_id']            = $data->wp_permit_id;
            $UPD['wp_permit_process_id']    = $data->wp_permit_process_id;
            $UPD['wp_permit_topic_id']      = $tid;
            if($this->createWorkPermitProcessTopic($UPD,$mod_user))
            {
                $suc++;
            } else {
                $err++;
            }
        }

        return $suc;
    }

    /**
     * 新增 工作許可證_流程_題目
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitProcessTopic($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_permit_id)) return $ret;

        $INS = new wp_permit_process_topic();
        $INS->wp_permit_id          = $data->wp_permit_id;
        $INS->wp_permit_process_id  = $data->wp_permit_process_id;
        $INS->wp_permit_topic_id    = $data->wp_permit_topic_id;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 工作許可證_流程_題目
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitProcessTopic($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_permit_process_topic::find($id);
        if(!isset($UPD->wp_permit_topic_id)) return $ret;
        //種類
        if(isset($data->wp_permit_topic_id) && $data->wp_permit_topic_id > 0 && $data->wp_permit_topic_id !== $UPD->wp_permit_topic_id)
        {
            $isUp++;
            $UPD->wp_permit_topic_id = $data->wp_permit_topic_id;
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
     * 取得 工作許可證_流程_題目
     *
     * @return array
     */
    public function getApiWorkPermitProcessTopicList($pid)
    {
        $ret = array();
        $mainAry = wp_permit::getSelect();
        //取第一層
        $data = wp_permit_process_topic::join('wp_permit_topic as t','t.id','=','wp_permit_process_topic.wp_permit_topic_id')->
                where('wp_permit_process_topic.wp_permit_process_id',$pid)->
                select('wp_permit_process_topic.*','t.name as topic')->
                orderby('wp_permit_process_topic.isClose')->
                orderby('t.show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['wp_permit']      = isset($mainAry[$v->wp_permit_id])? $mainAry[$v->wp_permit_id] : '';
                $data[$k]['topic']          = $v->topic;
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_流程_題目
     *
     * @return array
     */
    public function getApiWorkPermitProcessTopic($permit_id,$process_id,$worker_id = 0,$isShowAns = '',$targetAry = [],$isPatrol = 'N',$showType = 1)
    {
        $ret = array();
        //取第一層
        $data = wp_permit_process_topic::join('wp_permit_topic as t','t.id','=','wp_permit_process_topic.wp_permit_topic_id')->
                                         join('wp_topic_type as tp','tp.id','=','t.wp_topic_type')->
                                         join('wp_permit_process as p','p.id','=','wp_permit_process_topic.wp_permit_process_id')->
                                        where('wp_permit_process_topic.wp_permit_id',$permit_id)->
                                        select('wp_permit_process_topic.id','wp_permit_process_topic.wp_permit_process_id',
                                                'wp_permit_process_topic.wp_permit_topic_id','t.name as topic','t.bc_type_app',
                                                't.wp_topic_type as topic_type','t.isCheck as isCheck','t.wp_check_kind_id',
                                                'wp_permit_process_topic.isPatrol','tp.name as topic_type_name','tp.ans_amt',
                                                'p.isReturn','p.isRepeat')->
                                        where('wp_permit_process_topic.isClose','N')->
                                        where('t.isClose','N');
        //流程參數(判斷是否為數字或陣列)
        if(is_numeric($process_id) && $process_id > 0)
        {
            $data = $data->where('wp_permit_process_topic.wp_permit_process_id',$process_id);
        }elseif(is_array($process_id))
        {
            if(count($process_id))
            {
                $data = $data->whereIn('wp_permit_process_topic.wp_permit_process_id',$process_id);
            } else {
                $data = $data->whereIn('wp_permit_process_topic.wp_permit_process_id',[0]);
            }
        }
        $data = $data->orderby('t.show_order')->get();
        if(is_object($data))
        {
            $workOrderCheckData = wp_work_check::getSelect($worker_id,0);
            $workOrderCheckAry  = array_keys($workOrderCheckData);

            foreach ($data as $k => $v)
            {
                //題目項目限制: 如果有限定身份才能執行
                if($v->bc_type_app)
                {
                    if(!in_array($v->bc_type_app,$targetAry))
                    {
                        continue;
                    }
                }
                //題目項目限制: 如果不是巡邏
                if($isPatrol && $isPatrol !='ALL' && $v->isPatrol != $isPatrol)
                {
                    continue;
                }
                //附加檢點單
                if($worker_id && $v->wp_check_kind_id)
                {
                    if(!count($workOrderCheckAry) || !in_array($v->wp_check_kind_id,$workOrderCheckAry))
                    {
                        continue;
                    }
                }

                $tmp = [];
                $tmp['topic_id']                = $v->wp_permit_topic_id;
                //完整資訊
                if($showType == 1)
                {
                    $tmp['name']                    = $v->topic;
                    $tmp['topic_type']              = $v->topic_type;
                    $tmp['topic_type_name']         = $v->topic_type_name;
                    $tmp['wp_permit_process_id']    = $v->wp_permit_process_id;
                    $tmp['target']                  = $v->bc_type_app;//限定對象
                    //需要至少填寫幾題
                    $tmp['ans_amt']                 = ($v->ans_amt > 0) ? $v->ans_amt : wp_permit_topic_a::getAnsAmt($v->wp_permit_topic_id);
                }

                //題目
                $tmp['option']                  = $this->getApiWorkPermitTopicOption($v->wp_permit_topic_id,$worker_id,$isShowAns,$showType);

                //dd([$tmp,$v->wp_permit_topic_id,$worker_id,$isShowAns]);
                if($isShowAns == 'Y')
                {
                    if( (count($tmp['option'])) )$ret[] = $tmp;
                } else {
                    $ret[] = $tmp;
                }
            }
        }
        return $ret;
    }


}
