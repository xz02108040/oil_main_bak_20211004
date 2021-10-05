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
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\HTTCLib;
use App\Model\Emp\be_dept;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_log_door_today;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002106 [工作許可證]-回傳答案.
 * 目的：上傳答案
 *
 */
class D002106 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkPermitWorkImg,WorkPermitWorkProcessTopicOption,WorkOrderCheckRecord1Trait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use PushTraits;
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
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->ans          = (isset($jsonObj->ans))?           $jsonObj->ans : '';
            $this->reject_memo  = (isset($jsonObj->reject_memo))?   $jsonObj->reject_memo : '';
            $this->isPatrol     = (isset($jsonObj->isPatrol) && in_array($jsonObj->isPatrol,['Y','N']))?      $jsonObj->isPatrol : 'N';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $today              = date('Y-m-d');
            $yesterday          = SHCSLib::addDay(-1);

            $this->ans_work_process_id      = (isset($jsonObj->work_process_id))?$jsonObj->work_process_id : '';

            $wp_work                = wp_work::getData($this->work_id);

            if(!isset($wp_work->wp_permit_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200236';// 工作許可證不存在
            } else {
                $this->permit_id    = $wp_work->wp_permit_id;
                $this->b_factory_id = $wp_work->b_factory_id;
                $aproc              = $wp_work->aproc;
                $sdate              = $wp_work->sdate;
                $isClose            = $wp_work->isClose;
                $charge_memo        = $wp_work->charge_memo;
                $shift_id           = $wp_work->wp_permit_shift_id;
                $listData           = wp_work_list::getData($this->work_id);

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
                //2.2.1 非當日工作許可證
                if($isSuc == 'Y' && !in_array($sdate,[$today]) && $shift_id == 1)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200247';// 非當日工作許可證
                }
                //2.2.2 非當日工作許可證
                if($isSuc == 'Y' && !in_array($sdate,[$today,$yesterday]) && $shift_id == 2)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200247';// 非當日工作許可證
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
                //2.7 尚未啟動
                if($isSuc == 'Y' && $aproc === 'W')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200223';// 尚未啟動
                }
                //2.8 已經作廢
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200217';// 該工作許可證已作廢
                }

                if(isset($listData->id))
                {
                    $list_id                = $listData->id;
                    $this->work_process_id  = $listData->wp_work_process_id;
                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                } else {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200223';// 尚未啟動
                }
            }

            //3 登入檢核
            if($isSuc == 'Y')
            {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey    = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;
                //承攬商簽核，必須在廠
                $isExistDoor     = ($this->bc_type == 3)? view_log_door_today::isExist($this->b_factory_id,$this->b_cust_id,1) : 1;

                if(!$isExistDoor)
                {
                    $this->errCode  = 'E00200264';// 您目前不在廠區內
                }
                //為了配合昱俊測試 答案＆圖片上傳機制
                //如果有 作答記錄ＩＤ則忽略，回傳「作答記錄ＩＤ」
                elseif($this->ans_work_process_id && $this->ans_work_process_id != $this->work_process_id)
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
                }
                elseif(isset($this->bc_type))
                {
                    list($isOp,$this->myTarget,$this->myAppType) = HTTCLib::isTargetList($this->work_id,$this->b_cust_id);

                    if(!$isOp)
                    {
                        $processData    = wp_work_list::getNowProcessStatus($this->work_id);
                        $processName    = isset($processData['now_process'])? $processData['now_process'] : '';
                        $targetName     = isset($processData['process_target2'])? $processData['process_target2'] : '';
                        $this->errCode  = 'E00200226';// 身份資格不足，不可填寫工作許可證
                        $this->errParam1= $processName;// 身份資格不足，不可填寫工作許可證
                        $this->errParam2= $targetName;// 身份資格不足，不可填寫工作許可證
                    }
                    elseif($aproc != 'R' && !strlen($this->reject_memo) && !$this->setApiWorkPermitTopicRecord($this->myTarget,$this->work_id,$list_id,$this->work_process_id,$this->process_id,$this->ans,'Y',$this->isPatrol,$this->b_cust_id))
                    {
                        $this->errCode  = 'E00200225';// 請填寫完整資料
                    }
                    elseif($this->setApiWorkPermitTopicRecord($this->myTarget,$this->work_id,$list_id,$this->work_process_id,$this->process_id,$this->ans,'N',$this->isPatrol,$this->b_cust_id,$this->reject_memo))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200224';// 紀錄填寫失敗
                    }
                } else {
                    $this->errCode  = 'E00200102';// 無法取得帳號資訊
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

        //這張工作許可證目前進行階段ＩＤ
        $ret['now_process_id']  = isset($this->process_id)? $this->process_id : 0;
        if($this->reply == 'Y')
        {
            if($this->ans_work_process_id && $this->ans_work_process_id != $this->work_process_id)
            {
                $ret['work_process_id'] = $this->ans_work_process_id;
            } else {
                //目前階段
                $ret['work_process_id'] = $this->work_process_id;
            }
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
