<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
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
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Model\Supply\b_supply_member;
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
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002018
 * 目的： 停工用<啟動停工>
 *
 */
class D002018 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait;
    use PushTraits,WorkPermitWorkImg;
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
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->reject_memo  = (isset($jsonObj->reject_memo))?   $jsonObj->reject_memo : '';
            $this->sign_img     = (isset($jsonObj->sign_img))?      $jsonObj->sign_img : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $aproc              = isset($wp_work->aproc)? $wp_work->aproc : 0;
            $isClose            = isset($wp_work->isClose)? $wp_work->isClose : 'Y';

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //2.2 工作許可證不存在
            if($isSuc == 'Y')
            {
                if($isSuc == 'Y' && !$this->permit_id)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// 該工作許可證不存在
                }
                if($isSuc == 'Y' && $aproc === 'A')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200214';// 該工作許可證尚未審查
                }
                //2.6 審查不通過
                if($isSuc == 'Y' && $aproc === 'B')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200232';// 審查不通過
                }
                //2.6 已經作廢
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200237';// 該工作許可證已作廢
                }
                //2.6 該階段不可以提出停工申請
                if($isSuc == 'Y' && !in_array($aproc,['P','R']))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200231';// 該階段不可以提出停工申請
                }
                //2.7 請填寫事由
                if($isSuc == 'Y' && !$this->reject_memo)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200239';// 請填寫事由
                }
                //3 登入檢核
                if($isSuc == 'Y') {
                    $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                    $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                    $this->b_cust = view_user::find($this->b_cust_id);
                    $this->bc_type = $this->b_cust->bc_type;

                    if ($this->bc_type != '2')
                    {
                        $this->errCode = 'E00200238';// 承商不可以提出停工申請
                    } else {
                        if($this->stopWorkPermitWorkOrder($this->work_id,$this->reject_memo,$this->sign_img,$this->b_cust_id))
                        {
                            $this->reply     = 'Y';
                            $this->errCode   = '';
                            //推播： 工作許可證停工通知->承攬商：工地負責人/安衛人員
//                            $this->pushToSupplyPermitWorkStop($this->work_id,$this->reject_memo);
                        } else {
                            $this->errCode  = 'E00200241';// 申請停工失敗
                        }
                    }
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

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
