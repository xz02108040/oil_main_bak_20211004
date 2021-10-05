<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkOrderCheckRecord1Trait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkImg;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\HTTCLib;
use App\Model\Emp\be_dept;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_check_topic;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002019
 * 目的：上傳巡邏
 *
 */
class D002019 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkPermitWorkImg,WorkPermitWorkProcessTopicOption,WorkOrderCheckRecord1Trait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use PushTraits;
    use WorkPermitWorkOrderlineTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        //參數
        $isSuc    = 'Y';
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        $this->tokenType    = 'app';     //ＡＰＩ模式：ＡＰＰ
        $this->errCode      = 'E00004';//格式不完整
        $last_work_process_id = 0;
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->ans          = (isset($jsonObj->ans))?           $jsonObj->ans : '';
            $this->test         = (isset($jsonObj->test))?           $jsonObj->test : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $this->b_cust_id    = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
            $wp_work            = wp_work::getData($this->work_id);

            if(!isset($wp_work->wp_permit_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200236';// 工作許可證不存在
            }elseif (!isset($this->b_cust_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 工作許可證不存在
            } else {

                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                $this->permit_id    = $wp_work->wp_permit_id;
                $aproc              = $wp_work->aproc;
                $isClose            = $wp_work->isClose;
                $charge_memo        = $wp_work->charge_memo;
                $listData           = wp_work_list::getData($this->work_id);
                //TODO
                //檢查內容
                $ans                = $this->ans;
                if(count($ans))
                {
                    $isAns = 0;
                    foreach ($ans as $val)
                    {
                        if(isset($val->topic_a_id) && $val->topic_a_id == 96)
                        {
                            $record_stamp = isset($val->ans->record_stamp)? $val->ans->record_stamp : '';
                            if($record_stamp)
                            {
                                //檢查是否已經有相同時間的巡邏紀錄
                                $last_work_process_id = wp_work_check_topic::isRecordExist($this->work_id,2,$this->b_cust_id,$record_stamp);
                                if($last_work_process_id)
                                {
                                    $isAns = 1;
                                }
                                if(!$last_work_process_id)
                                {
                                    foreach ($val as $val2)
                                    {
                                        if(isset($val2->check_topic_a_id) && $val2->check_topic_a_id == 12)
                                        {
                                            if(isset($val2->ans) && !$val2->ans)
                                            {
                                                $isAns = 1;
                                            }
                                        }
                                    }
                                }
                            }
                            if($this->test)
                            {
                                dd($isAns,$record_stamp,$last_work_process_id,$val);
                            }
//
                        }
                    }
                    if(!$isAns)
                    {
//                        $isSuc          = 'N';
//                        $this->errCode  = 'E00200251';// 沒有作答
                    }
                } else {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200251';// 沒有作答
                }


                //2.1 帳號/密碼不可為空
                if($isSuc == 'Y' &&!isset($isExistToken->token))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200101';// 請重新登入
                }
                //2.2 工作許可證不存在
                if($isSuc == 'Y' && !$aproc)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// 該工作許可證不存在
                }
                //2.3 還在審查
                if($isSuc == 'Y' && $aproc == 'A')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200214';// 該工作許可證尚未審查
                }
                //2.5 已經收工
                if($isSuc == 'Y' && $aproc == 'F')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200216';// 該工作許可證已收工
                }
                //2.6 已經停工
                if($isSuc == 'Y' && $aproc == 'C')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200237';// 該工作許可證已停工
                    $this->errParam1= $charge_memo;// 該工作許可證已停工
                }
                //2.6 已經作廢
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200217';// 該工作許可證已作廢
                }
                //2.6 已經作廢
                if($isSuc == 'Y' && $aproc != 'R')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200242';// 巡邏會簽只能在
                }
                //2.6 已經作廢
                if($last_work_process_id)
                {
//                    $isSuc          = 'N';
//                    $this->errCode  = 'E00200252';// 巡邏會簽只能在
                }

                if(isset($listData->id))
                {
                    $list_id                = $listData->id;
                    $list_aproc             = $listData->aproc;
                    $this->workerAry        = [$wp_work->supply_worker,$wp_work->supply_safer];
                    $this->supply_worker    = $wp_work->supply_worker;
                    $this->supply_safer     = $wp_work->supply_safer;
                    $this->dept1            = $wp_work->be_dept_id1;
                    $this->dept2            = $wp_work->be_dept_id2;
                    $this->dept3            = $wp_work->be_dept_id3;
                    $this->dept4            = $wp_work->be_dept_id4;
                    $this->dept5            = be_dept::getParantDept($this->dept1);
                    $this->work_status      = $listData->work_status;
                    $this->sub_status       = $listData->work_sub_status;
                    $this->work_process_id  = $listData->wp_work_process_id;
                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                    $this->rule_app         = wp_permit_process::getRuleApp($this->process_id);
                    $this->target           = wp_permit_process_target::getTarget($this->process_id);
                } else {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200223';// 尚未啟動
                }
            }

            //3 登入檢核
            if($isSuc == 'Y')
            {
                if(!$last_work_process_id)
                {
                    list($isOp,$this->myTarget,$this->myAppType) = HTTCLib::genPermitTarget($this->b_cust_id,$this->bc_type,$this->workerAry,$this->target,$this->supply_worker,$this->supply_safer,$this->dept1,$this->dept2,$this->dept3,$this->dept4,$this->dept5);

                    if(!$isOp)
                    {
                        $processData    = wp_work_list::getNowProcessStatus($this->work_id);
                        $processName    = isset($processData['now_process'])? $processData['now_process'] : '';
                        $targetName     = isset($processData['process_target2'])? $processData['process_target2'] : '';
                        $this->errCode  = 'E00200226';// 身份資格不足，不可填寫工作許可證
                        $this->errParam1= $processName;// 身份資格不足，不可填寫工作許可證
                        $this->errParam2= $targetName;// 身份資格不足，不可填寫工作許可證
                    }
                    elseif($this->setApiWorkPermitTopicRecord($this->myTarget,$this->work_id,$list_id,$this->work_process_id,$this->process_id,$this->ans,'N','Y',$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200224';// 紀錄填寫失敗
                    }
                } else {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
                    $this->work_process_id = $last_work_process_id;
                }
            }
        }

        //2. 產生ＪＳＯＮ Ａrray
        $ret = $this->genReplyAry();
        //3. 回傳json 格式
        return $ret;
    }

    /**
     * 產生回傳內容陣列
     * @param $token
     * @return array
     */
    public function genReplyAry()
    {
        //回傳格式
        $ret = $this->getRet();

        if($this->reply == 'Y')
        {
            //目前階段
            $ret['work_process_id'] = $this->work_process_id;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
