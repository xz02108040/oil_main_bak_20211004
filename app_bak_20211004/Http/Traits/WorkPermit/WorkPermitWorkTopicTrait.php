<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use Lang;

/**
 * 工作許可證_施工單_題目_紀錄
 *
 */
trait WorkPermitWorkTopicTrait
{
    /**
     * 新增 工作許可證_施工單_題目_紀錄
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkTopic($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->ans_value)) return $ret;

        $INS = new wp_work_topic();
        $INS->wp_work_id            = $data->wp_work_id;
        $INS->wp_work_list_id       = $data->wp_work_list_id;
        $INS->wp_permit_topic_id    = $data->wp_permit_topic_id;
        $INS->ans_value             = $data->ans_value;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    public function uploadWorkPermitTopicImgRecord($work_id,$list_id,$work_process_id,$topic_a_id,$ans_val,$mod_user)
    {
        if(strlen($ans_val) <= 10) return false;
        list($topic_id,$option_type) = wp_permit_topic_a::getTopicIdList($topic_a_id);
        //新增填寫紀錄
        $INS = [];
        $INS['wp_work_id']              = $work_id;
        $INS['wp_work_list_id']         = $list_id;
        $INS['wp_work_process_id']      = $work_process_id;
        $INS['wp_permit_topic_id']      = $topic_id;
        $INS['wp_permit_topic_a_id']    = $topic_a_id;
        $INS['isImg']                   = 'Y';
        $INS['isGPS']                   = 'N';
        $INS['wp_check_id']             = 0;
        $INS['wp_work_img_id']          = 0;
        $INS['ans_value']               = '';
        //產生圖片記錄
        if(strlen($ans_val) > 10)
        {
            $filepath = config('mycfg.permit_path').date('Y/m/').$work_id.'/';
            $filename = $option_type.'_'.$topic_a_id.'_'.time().'.jpg';
            $wp_work_img_id = $this->createWorkPermitWorkImg($INS,$filepath,$filename,$ans_val,1,$mod_user);

            //圖片路徑
            $INS['wp_work_img_id']  = $wp_work_img_id;
            $INS['ans_value']       = ($wp_work_img_id)? $filepath.$filename : 0;
        }
        $ret = $this->createWorkPermitWorkTopicOption($INS,$mod_user);
        if($ret)
        {
            $process_id = wp_work_process::getProcess($work_process_id);
            $lostImgAry = $this->getLostImgTopic($work_id,$process_id);
            $UPD        = wp_work_process::find($work_process_id);
            $UPD->lost_img_amt  = count($lostImgAry);
            $UPD->mod_user      = $mod_user;
            $UPD->save();
        }
        return $ret;
    }

    /**
     * 修改 工作許可證_題目填寫功能
     * @param $id
     * @param $data
     * @param int $mod_user
     */
    public function setApiWorkPermitTopicRecord($targetAry,$work_id,$list_id,$work_process_id,$process_id,$data,$isCheck = 'N',$isPatrol = 'N', $mod_user = 1,$reject_memo = '', $isStop = '')
    {
        $ret = $ansAmt = $isIns = $lost_img_amt = 0;
        $recordAry1 = $recordAry2 = $test = [];
        //拒絕＆退回 功能判斷 2019-08-21
        $isReject       = (strlen($reject_memo))? 'Y' : 'N';
        $isStop        = (empty($isStop) && $isReject  == 'Y') ? 'Y' : 'N'; // 是否為送出停工
        $RejectMemo     = '';

        if($isReject == 'N')
        {
            $data = SHCSLib::toArray($data);
            if(!count($data)) return $ret;

            //1-1. 先轉換 填寫內容 =>陣列
            foreach ($data as $val)
            {
                //大題目
//            if(isset($val->topic_id) && isset($val->ans))
//            {
//                $recordAry1[$val->topic_id] = $val->ans;
//            }
                //子題目
                if(isset($val['topic_a_id']) && isset($val['ans']))
                {
                    $recordAry2[$val['topic_a_id']] = $val['ans'];

                    if(in_array($val['topic_a_id'],[93,200,207,215]) && $val['ans'] == 'N') // 轄區拒絕承商收工/暫停/復工申請
                    {
                        $isReject = 'Y';
                        $isStop = 'N';
                        if($val['topic_a_id'] == 93) $RejectMemo = '承商未收工完成';
                    }
                    if(in_array($val['topic_a_id'],[198,206,213]))
                    {
                        $RejectMemo = $val['ans'];
                    }
                }
            }
            if($isReject == 'Y' && $RejectMemo) $reject_memo = $RejectMemo;
//            dd($recordAry2,$isReject,$reject_memo,$RejectMemo);
            //如果沒有填寫內容
            if(!count($recordAry1) && !count($recordAry2) && $isReject == 'N') return $ret;

            //2-1. 取得
            $permit_id = wp_work::getPermit($work_id);
            //2-2. 客制參數
            $param_worker_identity  = sys_param::getParam('PERMIT_SUPPLY_WORKER',9);
            $param_etime            = sys_param::getParam('PERMIT_TOPIC_A_ID_ETIME',0);
            $param_lookworker1      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK1',0);
            $param_lookworker2      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK2',0);

            //2-10. 取得 需要填寫的題目
            $isShowAns = ($isCheck == 'Y')? 'Y' : '';
            $topicAry = $this->getApiWorkPermitProcessTopic($permit_id,$process_id,$work_id,$isShowAns,$targetAry,$isPatrol);
//        if(!$isShowAns)dd($topicAry,$recordAry2);
//            dd('ERR');
            if($total_ans_amt = count($topicAry))
            {
                if($isCheck == 'Y')
                {
                    foreach ($topicAry as $val1)
                    {
                        $topic_id = isset($val1['topic_id']) ?  $val1['topic_id'] : 0;
                        $isAns    = isset($val1['isAns']) ?     $val1['isAns'] : 'N';
                        $ans_amt  = isset($val1['ans_amt']) ?   $val1['ans_amt'] : 0;

                        //有填寫答案
                        if($topic_id && isset($recordAry1[$topic_id]) && $recordAry1[$topic_id])
                        {
                            //回答＋1
                            if($isAns == 'Y')
                            {
                                $ansAmt++;
                            }
                        }
                        else {
                            //題目
                            $opAmt = $isOK = 0;
                            if(count($val1['option']))
                            {
                                foreach ($val1['option'] as $val2)
                                {
                                    $topic_a_id = isset($val2['topic_a_id'])? $val2['topic_a_id'] : 0;
                                    $isAns      = isset($val2['isAns'])? $val2['isAns'] : 'N';
                                    if($topic_a_id > 0 && isset($recordAry2[$topic_a_id]))
                                    {
                                        //類型
                                        $option_type = isset($val2['wp_option_type'])? $val2['wp_option_type'] : 0;
                                        //答案
                                        if($option_type == 6)
                                        {
                                            $ans_val = isset($recordAry2[$topic_a_id]['img'])? $recordAry2[$topic_a_id]['img'] : $recordAry2[$topic_a_id];

                                        } elseif($option_type == 7) {
                                            //拍照答案 配合 2019-10-02 圖片補上傳功能，開放不在檢查是否有無上傳
                                            $ans_val = 1;
                                        } else {
                                            $ans_val = $recordAry2[$topic_a_id];
                                        }
                                        //如果有答案
                                        if($ans_val) $opAmt++;
                                    }
                                }
                            }
                            if($opAmt >= $ans_amt)
                            {
                                //回答＋1
                                $ansAmt++;
                                $isOK = 1;
                            }
                            if(!$isOK) $test[$topic_id] =([$val1,$val2,$opAmt,$ans_amt,$isOK]);
                        }
                    }
                } else {
                    foreach ($topicAry as $val1)
                    {
                        $topic_id           = isset($val1['topic_id']) ? $val1['topic_id'] : 0;
                        //有填寫答案
                        if($topic_id && isset($recordAry1[$topic_id]) && $recordAry1[$topic_id])
                        {
                            //答案
                            $ans_val = $recordAry1[$topic_id];

                            //新增填寫紀錄
                            $INS = [];
                            $INS['wp_work_id']              = $work_id;
                            $INS['wp_work_list_id']         = $list_id;
                            $INS['wp_work_process_id']      = $work_process_id;
                            $INS['wp_permit_topic_id']      = $topic_id;

                            $INS['ans_value']               = $ans_val;//文字紀錄
                            $test[] =$INS; //測試用
                            //新增 填寫紀錄
                            if($this->createWorkPermitWorkTopic($INS,$mod_user))
                            {
                                $isIns++;
                            }
                        }
                        else {
                            //題目
                            if(count($val1['option']))
                            {
                                foreach ($val1['option'] as $val2)
                                {
                                    $topic_a_id = isset($val2['topic_a_id'])? $val2['topic_a_id'] : 0;
                                    /**
                                     * 客製功能 topic_a_id
                                     * 121 : 工作許可證結束時間
                                     *  25 : 看火者
                                     *  26 : 監視者
                                     */
//                                    if($topic_a_id == $param_etime)
//                                    {
//                                        $ate_time_default   = sys_param::getParam('PERMIT_DEFAULT_ETIME');
//                                        $old_etime = $recordAry2[$topic_a_id];
//                                        $old_etime = date('Y-m-d').' '.$old_etime;
//                                        if( strtotime($old_etime) < time()) $recordAry2[$topic_a_id] = $ate_time_default;
//                                    }
                                    if($topic_a_id == $param_lookworker1){
                                        //TODO 先不擋 是否為工作許可證內的人
                                        if($lookworker1 = $recordAry2[$topic_a_id])
                                        {
                                            //wp_work_worker::isExist($work_id,$lookworker1,$param_worker_identity);
                                        }
                                    }
//                                if($param_stime == $topic_a_id) dd($recordAry2);
                                    //如果有相對應的答案，則檢查，並寫入
                                    if($topic_a_id > 0 && isset($recordAry2[$topic_a_id]) )
                                    {
                                        $ans_val        = $recordAry2[$topic_a_id];
                                        $option_type    = isset($val2['wp_option_type'])? $val2['wp_option_type'] : 0;
                                        $wp_check_id    = isset($val2['wp_check_id'])? $val2['wp_check_id'] : 0;
                                        $reject_memo_t  = '';
                                        //類別：純顯示 不紀錄
                                        if($option_type == 5) continue;
                                        //類別：拍照，簽名。
                                        if($option_type == 6)
                                        {
                                            $ans_val    = $recordAry2[$topic_a_id];
                                        }

                                        //新增填寫紀錄
                                        $INS = [];
                                        $INS['wp_work_id']              = $work_id;
                                        $INS['wp_work_list_id']         = $list_id;
                                        $INS['wp_work_process_id']      = $work_process_id;
                                        $INS['wp_permit_topic_id']      = $topic_id;
                                        $INS['wp_permit_topic_a_id']    = $topic_a_id;

                                        $INS['wp_check_id'] = $wp_check_id;
                                        $INS['isImg'] = $val2['isImg'];
                                        $INS['isGPS'] = $val2['isGPS'];
                                        $INS['isLostImg']  = 'N';
                                        if($wp_check_id)
                                        {
                                            $check_value = (object)$ans_val;
                                            $INS['check_value'] = $ans_val;
                                            $INS['ans_value']   = 'CHECK_'.$wp_check_id;
                                            //檢查是否有無補上傳圖片
                                            if(isset($check_value->option))
                                            {
                                                foreach ($check_value->option as $chk_val)
                                                {
                                                    if(isset($chk_val['ans']) && $chk_val['ans'] == 'img')
                                                    {
                                                        $lost_img_amt ++;
                                                        $INS['isLostImg']  = 'Y';
                                                    }
                                                }
                                            }
                                        }
                                        elseif($val2['isGPS'] == 'Y')
                                        {
                                            $INS['ans_value'] = $ans_val;
                                            //切割ＧＰＳ文字字串
                                            $GPSAry = explode(',',$ans_val);
                                            $INS['GPSX'] = isset($GPSAry[0])? $GPSAry[0] : 0;
                                            $INS['GPSY'] = isset($GPSAry[1])? $GPSAry[1] : 0;
                                        }
                                        elseif($val2['isImg'] == 'Y')
                                        {
                                            //產生圖片記錄
                                            if(strlen($ans_val) > 10)
                                            {
                                                $filepath = config('mycfg.permit_path').date('Y/m/').$work_id.'/';
                                                $filename = $option_type.'_'.$topic_a_id.'_'.time().'.jpg';
                                                $wp_work_img_id = $this->createWorkPermitWorkImg($INS,$filepath,$filename,$ans_val,0,$mod_user);

                                                //圖片路徑
                                                $INS['wp_work_img_id']  = $wp_work_img_id;
                                                $INS['ans_value']       = ($wp_work_img_id)? $filepath.$filename : 0;
                                            } else {
                                                //如果確定後補上傳
                                                if($ans_val == 'img'){
                                                    $INS['isLostImg']  = 'Y';
                                                    $lost_img_amt ++;
                                                }
                                                $INS['wp_work_img_id']  = 0;
                                                $INS['ans_value']       = '';
                                            }
                                            $INS['reject_memo']     = $reject_memo_t;
                                        } else {
                                            if(in_array($topic_a_id,[122,123,124,127,133,134]) && in_array($ans_val,['=','N','Y']))
                                            {
                                                $ans_val = '';
                                            }
                                            //文字紀錄
                                            $INS['ans_value'] = $ans_val;
                                        }

                                        $test[] =$INS; //測試用
//                                    if($topic_a_id == 209)dd($INS);
//                                    dd($test);
                                        //新增 填寫紀錄
                                        if($this->createWorkPermitWorkTopicOption($INS,$mod_user))
                                        {
                                            $isIns++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $ansAmt = $total_ans_amt = $isIns = 1;
        }

        if($isCheck == 'Y')
        {
//            dd([$test,'ansAmt'=>$ansAmt,'total_ans_amt'=>$total_ans_amt,'topicAry'=>$topicAry,
//                'recordAry1'=>$recordAry1,'recordAry2'=>$recordAry2]);
            return ($ansAmt == $total_ans_amt)? 1 : 0;
        } else {
            //更新 程序，進入下一個
            if($isIns)
            {
                //解除鎖定
                $tmp = [];
                $tmp['isLock'] = 'N';
                $this->setWorkPermitWorkOrderList($list_id,$tmp,$mod_user);
//                dd($isReject,$reject_memo);
                //判斷是否進入下一個階段
                $this->setWorkPermitWorkOrderProcessSet($work_id,$list_id,$process_id,$work_process_id,[$isReject,$reject_memo,'','',$isStop],$lost_img_amt,$mod_user);
            }
            return $isIns;
        }
    }

    /**
     * 修改 工作許可證_施工單_題目_紀錄
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkTopic($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_topic::find($id);
        if(!isset($UPD->ans_value)) return $ret;
        //名稱
        if(isset($data->ans_value) && $data->ans_value && $data->ans_value !== $UPD->ans_value)
        {
            $isUp++;
            $UPD->ans_value = $data->ans_value;
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
     * 取得 工作許可證_施工單_題目_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitWorkTopicList()
    {
        $ret = array();
        //取第一層
        $data = wp_work_topic::where('isClose','N')->orderby('id','desc')->get();

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
     * 取得 工作許可證_施工單_題目_紀錄
     *
     * @return array
     */
    public function getApiWorkPermitWorkTopicRecord($work_id,$list_id,$appkey = '')
    {
        $ret = $recordAry1 = $recordAry2 = array();
        if(!$work_id) return $ret;

        $param_lookworker1      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK1',0);
        $param_lookworker2      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK2',0);

        $permit_id  = wp_work::getPermit($work_id);
        //目前完成到的階段
        $processAry = wp_work_process::getListProcess($list_id);
//        dd($processAry);
        //1.取得 需要填寫的題目
        $topicAry = $this->getApiWorkPermitProcessTopic($permit_id,$processAry,$work_id);

        //2. 取得 作答的答案
        $data2 = wp_work_topic_a::where('wp_work_id',$work_id)->where('isClose','N')->get();
        if(is_object($data2))
        {
            $ynAry = SHCSLib::getCode('YN_SELECT');
            foreach ($data2 as $k => $v)
            {
                //IMG 圖片
                if($v->wp_work_img_id)
                {
                    $ans_val = url('img/Permit').'/'.SHCSLib::encode($v->wp_work_img_id).'?key='.$appkey;
                }
                elseif(!$v->wp_work_img_id && $v->isImg == 'Y' && $v->ans_value)
                {
                    $ans_val = '';
                }
                //檢點單
                elseif($v->wp_work_check_topic_id)
                {
                    $ans_val = $this->getApiWorkPermitCheckTopic($work_id,$v->wp_work_check_topic_id,$appkey);
                    //if($v->wp_work_check_topic_id == 27122) dd($ans_val);
                }
                else {
                    $ans_val = isset($ynAry[$v->ans_value])? $ynAry[$v->ans_value] : $v->ans_value;
                    //如果是人
                    if( in_array($v->wp_permit_topic_a_id,[$param_lookworker1,$param_lookworker2]) )
                    {
                        $ans_tmpAry = [];
                        $ans_valAry = explode(',',$ans_val);
                        foreach ($ans_valAry as $user_id)
                        {
                            $ans_tmpAry[] = User::getName($user_id);
                        }
                        $ans_val = implode(',',$ans_tmpAry);
                    }
                }
                $recordAry2[$v->wp_permit_topic_a_id] = $ans_val;
            }
        }
        //
        if(count($topicAry) && count($recordAry2))
        {
            //dd([$topicAry,$recordAry]);
            foreach ($topicAry as $key => $val)
            {
                if(isset($val['option']) && is_array($val['option']) && count($val['option']))
                {
                    foreach ($val['option'] as $key2 => $val2)
                    {
                        $topic_a_id     = isset($val2['topic_a_id'])? $val2['topic_a_id'] : 0;
                        $wp_option_type = isset($val2['wp_option_type'])? $val2['wp_option_type'] : 0;
                        $check_topic_id = isset($val2['check_topic_id'])? $val2['check_topic_id'] : 0;

                        if($topic_a_id)
                        {
                            if(isset($recordAry2[$topic_a_id]))
                            {
                                if($wp_option_type == 9)
                                {
                                    foreach ($recordAry2[$topic_a_id] as $checkAnsVal)
                                    {
                                        if(isset($checkAnsVal['check_topic_id']) && $checkAnsVal['check_topic_id'] == $check_topic_id)
                                        {
                                            $topicAry[$key]['option'][$key2]['option'] = $checkAnsVal['option'];
                                        }
                                    }
                                } else {
                                    $topicAry[$key]['option'][$key2]['ans_value']   = $recordAry2[$topic_a_id];
                                    $topicAry[$key]['option'][$key2]['value']       = $recordAry2[$topic_a_id];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $topicAry;
    }

    /**
     * 取得 工作許可證_施工單_題目_紀錄「該階段之內容」
     *
     * @return array
     */
    public function getMyWorkPermitProcessTopicAns($work_id,$work_process_id,$appkey = '', $myTarget = [1,2,3,4,5,6,7,8,9], $isLook = 'N')
    {
        //參數
        $permit_id              = wp_work::getPermit($work_id);
        $wp_permit_process_id   = wp_work_process::getProcess($work_process_id);
        $reject_type            = wp_permit_process::getRuleReject($wp_permit_process_id);  //是否退件
        $isRunStatus            = wp_permit_process::getIsRunStatus($wp_permit_process_id); //
        $processAry             = [$wp_permit_process_id];
        //2019-09-10 停工
        list($isReject,$reject_memo) = wp_work_process::getRejectStatusList($work_process_id);
//        if($reject_memo) dd($reject_memo);

        //1. 取得 需要填寫的題目
        $isPatrol = ($isLook == 'Y')? '' : 'N';
        $topicAry = $this->getApiWorkPermitProcessTopic($permit_id,$processAry,$work_id,'',$myTarget,$isPatrol);

        //2. 取得 作答的答案
        $ansAry = $this->getProcessTopicAns($work_id,$work_process_id,$appkey);
        //3. 組合答案
        if(count($topicAry))
        {
            //if($work_process_id == 51790) dd([$topicAry,$ansAry]);
            foreach ($topicAry as $key => $val)
            {
                $topicAry[$key]['work_process_id'] = $work_process_id;
                if(isset($val['option']) && is_array($val['option']) && count($val['option']))
                {
                    foreach ($val['option'] as $key2 => $val2)
                    {
                        //題目ＩＤ
                        $topic_a_id     = isset($val2['topic_a_id'])? $val2['topic_a_id'] : 0;
                        $wp_option_type = isset($val2['wp_option_type'])? $val2['wp_option_type'] : 0;
                        $check_topicAry = isset($val2['check'])? $val2['check'] : [];
                        $isAns          = 'N';
                        if($topic_a_id)
                        {
                            //如果有答案
                            if(isset($ansAry[$topic_a_id]))
                            {
                                $isAns = 'Y';
                                if($wp_option_type == 9 && is_array($ansAry[$topic_a_id]))
                                {
                                    foreach ($check_topicAry as $ckTopicKey => $ckTopicAry)
                                    {
                                        $check_topic_id = isset($ckTopicAry['check_topic_id'])? $ckTopicAry['check_topic_id'] : 0;
                                        if(!$check_topic_id) continue;
                                        foreach ($ansAry[$topic_a_id] as $checkAnsVal)
                                        {
                                            if(isset($checkAnsVal['check_topic_id']) && $check_topic_id == $checkAnsVal['check_topic_id'])
                                            {
                                                //$topicAry[$key]['option'][$key2]['check'][] = $checkAnsVal;
                                                if (is_array($checkAnsVal['option'])) {
                                                    foreach ($checkAnsVal['option'] as $optionKey => $optionVal) {
                                                        if($checkAnsVal['option'][$optionKey]['ans_value'] == "Y")  $checkAnsVal['option'][$optionKey]['ans_value'] = "是";
                                                        if($checkAnsVal['option'][$optionKey]['ans_value'] == "N")  $checkAnsVal['option'][$optionKey]['ans_value'] = "否";
                                                        if($checkAnsVal['option'][$optionKey]['ans_value'] == "=")  $checkAnsVal['option'][$optionKey]['ans_value'] = "無關";
                                                    }
                                                }
                                                $check_topicAry[$ckTopicKey]['option'] = $checkAnsVal['option'];
                                            }
                                        }
                                    }
                                    $topicAry[$key]['option'][$key2]['check'] = $check_topicAry;
                                    //dd($topicAry[$key]['option'][$key2]['check'],$check_topicAry);

                                } else {
                                    $topicAry[$key]['option'][$key2]['ans_value']   = $ansAry[$topic_a_id];
                                }
                            }
                        }
                    }
                }
                //如果是 執行階段 ，只顯示有作答的內容
                if($isRunStatus == 'Y' && $isAns == 'N')
                {
                    unset($topicAry[$key]);
                }
            }
            $topicTmpAry = [];
            foreach ($topicAry as $val )
            {
                $topicTmpAry[] = $val;
            }
            $topicAry = $topicTmpAry;
            //如果這個階段被停工
            if($isReject == 'Y')
            {
                $chargeTitle  = ($reject_type == 2)?'base_10938' : 'base_10940';

                if($wp_permit_process_id == 21) $chargeTitle = 'base_10950';
                if($wp_permit_process_id == 23) $chargeTitle = 'base_10952';
                if($wp_permit_process_id == 25) $chargeTitle = 'base_10954';
                
                $topicTmp = [];
                $topicTmp['topic_id'] = -1;
                $topicTmp['topic'] = Lang::get('sys_base.'.$chargeTitle);
                $topicTmp['topic_type'] = 1;
                $topicTmp['topic_type_name'] = '';
                $topicTmp['wp_permit_process_id'] = $wp_permit_process_id;
                $topicTmp['work_process_id'] = $work_process_id;
                $topicTmp['ans_amt']  = 1;
                $topicTmp['isCheck']  = 'N';
                $topicTmp['isReturn'] = 'N';

                //內容
                $rejectTmp = [];
                $rejectTmp['topic_a_id'] = -1;
                $rejectTmp['wp_option_type'] = 2;
                $rejectTmp['wp_option_type_name'] = '';
                $rejectTmp['name']          = Lang::get('sys_base.base_10931');
                $rejectTmp['ans_type']      = 1;
                $rejectTmp['value']         = $reject_memo;
                $rejectTmp['ans_value']     = $reject_memo;
                $rejectTmp['wp_check_id']   = 0;
                $rejectTmp['isAns']         = 'N';
                $rejectTmp['isImg']         = 'N';
                $rejectTmp['isGPS']         = 'N';
                $rejectTmp['engineering_identity_id'] = 0;
                $rejectTmp['ans_select'] = '';
                $topicTmp['option'][] = $rejectTmp;
                //停工事由
                $topicAry[] = $topicTmp;
            }

            //停工/暫停/復工
            if($isReject != 'Y' && in_array($wp_permit_process_id, [21, 23, 25]) ){

                if($wp_permit_process_id == 21) $chargeTitle = 'base_10956';
                if($wp_permit_process_id == 23) $chargeTitle = 'base_10957';
                if($wp_permit_process_id == 25) $chargeTitle = 'base_10958';


                $topicTmp = [];
                $topicTmp['topic_id'] = -1;
                $topicTmp['topic'] = Lang::get('sys_base.'.$chargeTitle);

                $topicTmp['topic_type'] = 1;
                $topicTmp['topic_type_name'] = '';
                $topicTmp['wp_permit_process_id'] = $wp_permit_process_id;
                $topicTmp['work_process_id'] = $work_process_id;
                $topicTmp['ans_amt']  = 1;
                $topicTmp['isCheck']  = 'N';
                $topicTmp['isReturn'] = 'N';

                $memo = '';
                if($wp_permit_process_id == 21){
                    $memo = wp_work_process_topic::getTopicAns($work_id, 198, $work_process_id);
                }else if($wp_permit_process_id == 23){
                    $memo = wp_work_process_topic::getTopicAns($work_id, 206, $work_process_id);
                }else if($wp_permit_process_id == 25){
                    $memo = wp_work_process_topic::getTopicAns($work_id, 213, $work_process_id);
                }

                $allowTmp = [];
                $allowTmp['topic_a_id'] = -1;
                $allowTmp['wp_option_type'] = 2;
                $allowTmp['wp_option_type_name'] = '';
                $allowTmp['name']          = Lang::get('sys_base.base_10931');
                $allowTmp['ans_type']      = 1;
                $allowTmp['value']         = $memo;
                $allowTmp['ans_value']     = $memo;
                $allowTmp['wp_check_id']   = 0;
                $allowTmp['isAns']         = 'N';
                $allowTmp['isImg']         = 'N';
                $allowTmp['isGPS']         = 'N';
                $allowTmp['engineering_identity_id'] = 0;
                $allowTmp['ans_select'] = '';
                $topicTmp['option'][] = $allowTmp;

                $topicAry[] = $topicTmp;
            }
        }


        //4. 回傳結果
        return $topicAry;
    }

    public function getProcessTopicAns($work_id,$work_process_id,$appkey,$isApi = 0, $optionIdExistAry = [])
    {
        $ansAry = [];
        $ynAry                  = SHCSLib::getCode('YN_SELECT');
        $param_lookworker1      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK1',25);
        $param_lookworker2      = sys_param::getParam('PERMIT_TOPIC_A_ID_LOOK_WORK2',26);

        $topicAnsData = wp_work_process_topic::where('wp_work_id',$work_id)->where('wp_work_process_id',$work_process_id)->where('isClose','N');
        if(count($optionIdExistAry))
        {
            $topicAnsData = $topicAnsData->whereIn('wp_permit_topic_a_id',$optionIdExistAry);
        }
//        if($work_process_id == 52083)dd($topicAnsData->get());
        if($topicAnsData->count())
        {
            $topicAnsAry = $topicAnsData->get();
            foreach ($topicAnsAry as $k => $v)
            {
                $wp_option_type  = wp_permit_topic_a::getType($v->wp_permit_topic_a_id);
                //工程身份不要 回傳答案
                if($wp_option_type == 10) continue;
                //IMG 圖片
                if($v->wp_work_img_id)
                {
                    $imgurl      = url('img/Permit/').'/';
                    $encodeImgId = SHCSLib::encode($v->wp_work_img_id);
                    $appkeyParam = ($appkey)? '&key='.$appkey : '';
                    $imgSize     = ($wp_option_type != 6)? '&size=640' : '';
                    $ans_val     = $imgurl.$encodeImgId.'?'.$appkeyParam.$imgSize;
                }
                //圖片異常
                elseif(!$v->wp_work_img_id && $v->isImg == 'Y' && $v->ans_value)
                {
                    $ans_val = '';
                }
                //檢點單
                elseif($v->wp_work_check_topic_id)
                {
                    $ans_val = $this->getApiWorkPermitCheckTopic($work_id,$v->wp_work_check_topic_id,$appkey);
//                    if($work_process_id == 52090) dd($work_process_id,$v->wp_work_check_topic_id,$ans_val);
                }
                else {
                    //危害告知
                    if($wp_option_type == 15)
                    {
                        $ynAry   = SHCSLib::getCode('NOTICE',0);
                        $ans_val = isset($ynAry[$v->ans_value])? $ynAry[$v->ans_value] : $v->ans_value;
                    } else {
                        //是＆否＆無關
                        $ans_val = isset($ynAry[$v->ans_value])? $ynAry[$v->ans_value] : $v->ans_value;
                        //如果是人
                        if( in_array($v->wp_permit_topic_a_id,[$param_lookworker1,$param_lookworker2]) )
                        {
                            $ans_tmpAry = [];
                            $ans_valAry = explode(',',$ans_val);
                            foreach ($ans_valAry as $user_id)
                            {
                                $ans_tmpAry[] = User::getName($user_id);
                            }
                            $ans_val = implode(',',$ans_tmpAry);
                        }
                    }

                }
                //作答
                if($isApi)
                {
                    $tmp = [];
                    $tmp['topic_a_id']      = $v->wp_permit_topic_a_id;
                    $tmp['ans_value']       = ($v->wp_work_check_topic_id && isset($ans_val[0]) && isset($ans_val[0]['option']))? $ans_val[0]['option'] : $ans_val;
                    $tmp['check_topic_id']  = ($v->wp_work_check_topic_id && isset($ans_val[0]) && isset($ans_val[0]['check_topic_id']))? $ans_val[0]['check_topic_id'] : 0;
                    $tmp['isCheckOrder']    = ($v->wp_work_check_topic_id)? 'Y' : 'N';
                    $ansAry[] = $tmp;
                } else {
                    $ansAry[$v->wp_permit_topic_a_id] = $ans_val;
                }
            }
        }
        return $ansAry;
    }
}
