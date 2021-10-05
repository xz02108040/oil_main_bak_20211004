<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002004 工作許可證-所有階段->題目.
 * 目的：工作許可證-所有階段->題目
 * 淘汰功能
 */
class D002004 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderCheckTrait;
    use WorkPermitProcessTopicTrait,WorkPermitWorkTopicTrait,WorkPermitWorkTopicOptionTrait,WorkPermitWorkProcessTopicOption;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait,WorkPermitTopicOptionTrait,WorkCheckTopicTrait;
    use WorkCheckTrait,WorkCheckTopicOptionTrait,WorkPermitProcessTrait;
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
            $token                  = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id          = (isset($jsonObj->id))?            $jsonObj->id : '';
            $isExistToken           = TokenLib::isTokenExist(0, $token,$this->tokenType);
            //工作許可證
            $wp_work                = wp_work::getData($this->work_id);
            $this->permit_id        = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $aproc                  = isset($wp_work->aproc)? $wp_work->aproc : 0;
            $work_close             = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            $charge_memo            = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';
            $this->supply_worker    = $wp_work->supply_worker;
            $this->supply_safer     = $wp_work->supply_safer;
            $this->dept1            = $wp_work->be_dept_id1;
            $this->dept2            = $wp_work->be_dept_id2;
            $this->dept3            = $wp_work->be_dept_id3;
            $this->dept4            = $wp_work->be_dept_id4;
            $this->dept5            = be_dept::getParantDept($this->dept1);
            $this->permit_danger    = $wp_work->wp_permit_danger;

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
            //2.3 還在審查
            if($isSuc == 'Y' && $aproc == 'A')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200214';// 該工作許可證未審查
            }
            //2.4 審查不通過
            if($isSuc == 'Y' && $aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200232';// 審查不通過
            }
            //2.9 尚未啟動
            if($isSuc == 'Y' && $aproc === 'P')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200223';// 尚未啟動
            }
            //2.5 已經收完
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
                $this->errParam1  = $charge_memo;// 該工作許可證已停工
            }
            //2.7 已經作廢
            if($isSuc == 'Y' && $work_close === 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200217';// 該工作許可證已作廢
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if(isset($this->bc_type))
                {
                    $listData               = wp_work_list::getData($this->work_id);
                    $this->list_id          = isset($listData->id) ? $listData->id : 0;

                    if(!$this->list_id)
                    {
                        $this->errCode  = 'E00200214';// 已經作廢
                    } else {
                        //已經啟動
                        $this->reply = 'Y';
                        $this->errCode = '';
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

        if($this->reply == 'Y')
        {
            //參數
            $patrolData = [];
            $targetAry  = [1,2,3,4,5,6,7,8,9];
            //全部的題目
            $ret['myTarget']    = SHCSLib::genPermitSelfTarget($this->b_cust_id,$this->bc_type,$this->supply_worker,$this->supply_safer,$this->dept1,$this->dept2,$this->dept3,$this->dept4,$this->dept5,1);
            $ret['permit_at']   = wp_permit::getAt(1);
            $ret['permit']      = $this->getApiWorkPermitProcessAll(1,$this->work_id,$this->permit_danger,$this->dept4,$targetAry,$this->apiKey);
            if($this->bc_type  == 2)
            {
                $patrolData = $this->getApiWorkPermitProcessTopic(1,7,0,'',[9],'Y');
            }
            $ret['patrol']  = $patrolData ;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
