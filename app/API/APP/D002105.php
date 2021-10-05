<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\HTTCLib;
use App\Model\Emp\b_cust_e;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002105 [工作許可證]-鎖單.
 * 目的：鎖單　特定工作許可證階段　不允許他人簽核
 *
 */
class D002105 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait;
    use WorkPermitWorkOrderListTrait;
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
        $this->errCode2     = '';
        $max_allow_time     = strtotime(date('Y-m-d 16:00:00'));

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token                  = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id          = (isset($jsonObj->id))?    $jsonObj->id : 0;
            $this->isLock           = (isset($jsonObj->isLock) && in_array($jsonObj->isLock,['Y','N']))?   $jsonObj->isLock : 'Y';

            $isExistToken           = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $listData               = wp_work_list::getData($this->work_id);
            $wp_work_list_id        = isset($listData->id)? $listData->id : 0;
            $this->work_process_id  = isset($listData->wp_work_process_id)? $listData->wp_work_process_id : 0;
            $this->process_id       = wp_work_process::getProcess($this->work_process_id);
            $wp_work                = wp_work::getData($this->work_id);
            $this->permit_id        = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $this->work_aproc       = isset($wp_work->aproc)? $wp_work->aproc : '';
            $this->shift_id         = isset($wp_work->wp_permit_shift_id)? $wp_work->wp_permit_shift_id : 0;
            $dept1                  = isset($wp_work->be_dept_id1)? $wp_work->be_dept_id1 : 0;
            $charge_memo            = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';
            $work_close             = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            //dd($this->work_id);
            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
            //2.2 請選擇工作許可證

            if(!$this->work_aproc)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200213';// 該工作許可證不存在
            }
            //2.3 還在審查
            if($isSuc == 'Y' && $this->work_aproc == 'A')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200214';// 該工作許可證未審查
            }
            //2.4 審查不通過
            if($isSuc == 'Y' && $this->work_aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200232';// 審查不通過
            }
            //2.5 已經收完
            if($isSuc == 'Y' && $this->work_aproc == 'F')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200216';// 該工作許可證已收工
            }
            //2.6 已經停工
            if($isSuc == 'Y' && $this->work_aproc == 'C')
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
            //該工作許可證已啟動
            if($isSuc == 'Y' && $this->work_aproc == 'W')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200223';// 該工作許可證尚未啟動
            }
            //該工作許可證已啟動
            list($lock_user,$lock_stamp) = wp_work_list::isLock($wp_work_list_id);
            if($isSuc == 'Y' && $lock_user && $lock_user != $this->b_cust_id && $this->isLock == 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200256';// 該工作許可證已啟動
                $this->errParam1  = User::getName($lock_user);// 鎖定人員
                $this->errParam2  = $lock_stamp;// 鎖定時間
            }
            if($isSuc == 'Y' && !$lock_user && $this->isLock == 'N')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200257';// 該階段目前尚未負責簽核
            }

            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;


                if(isset($this->bc_type) )
                {
                    //所處部門
                    $dept   = ($this->bc_type == 2)? view_dept_member::getDept($this->b_cust_id) : 0;
                    $myTitle= ($this->bc_type == 2)? view_dept_member::getTitle($this->b_cust_id) : 0;
                    //檢核是否
                    list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::isTargetList($this->work_id,$this->b_cust_id);

                    if(!$this->isOp)
                    {
                        $this->errCode  = 'E00200243';// 您非這階段的負責人
                    }elseif($this->isOp && $this->work_aproc == 'R' && $dept && $dept1 != $dept && $myTitle != 4)
                    {
                        //施工階段(非轄區人員，無需定期氣體偵測環境作業)
                        $this->errCode  = 'E00200248';//  非轄區人員，無需定期氣體偵測環境作業
                    }elseif($this->isOp && $this->work_aproc == 'R')
                    {
                        //施工階段
                        $this->reply        = 'Y';
                        $this->errCode      = '';
                    } else {
                        $tmp = [];
                        $tmp['isLock'] = $this->isLock;
                        if($this->setWorkPermitWorkOrderList($wp_work_list_id,$tmp,$this->b_cust_id))
                        {
                            $this->reply     = 'Y';
                            $this->errCode   = '';
                        } else {
                            $this->errCode  = ($this->isLock == 'Y')?'E00200258': 'E00200259';// 鎖定／解鎖失敗
                        }
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
        $ret['now_process_id']  = $this->process_id;
        if($this->reply == 'Y')
        {
            $ret['isRepeat'] = ($this->work_aproc == 'R')? 'Y' : 'N';
            //$ret['isPatrol'] = ($this->errCode2 == 'E00200248')? 'Y' : 'N';
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
