<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
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
 * D002107
 * 目的： 工作許可證歷程
 *
 */
class D002107 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait;
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
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $aproc              = isset($wp_work->aproc)? $wp_work->aproc : 0;
            $isClose            = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            //dd([$wp_work,$listData,$this->process_id,$this->target]);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
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
            //3 登入檢核
            if($isSuc == 'Y') {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust = view_user::find($this->b_cust_id);
                $this->bc_type = $this->b_cust->bc_type;

                if ($this->bc_type) {
                    $listData = wp_work_list::getData($this->work_id);
                    $this->list_id = isset($listData->id) ? $listData->id : 0;

                    if (!$this->list_id) {
                        $this->errCode = 'E00200214';// 已經作廢
                    } else {
                        //已經啟動
                        $this->reply = 'Y';
                        $this->errCode = '';
                    }
                } else {
                    $this->errCode = 'E00200102';// 無法取得帳號資訊
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
            //顯示 工作許可證 歷程
            $history = $this->getMyPermitWorkOrderProcess($this->work_id,$this->list_id,$this->apiKey);
            //歷程
            $ret['history']         = $history;
            //dd($history);

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
