<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTargetTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Lib\HtmlLib;
use App\Lib\HTTCLib;
use App\Model\Emp\b_cust_e;
use App\Model\Engineering\e_project_l;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002114 [工作許可證]-補人作業.
 * 目的： 承攬商(申請補人)
 * 目的： 監造(直接補人)
 *
 */
class D002114 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait;
    use WorkPermitWorkOrderlineTrait,WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderCheckTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,WorkPermitProcessTargetTrait;
    use WorkPermitProcessTopicTrait,WorkPermitWorkProcessTopicOption,WorkPermitTopicOptionTrait;
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
        $this->addmemberAry = [];
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->addmember    = (isset($jsonObj->addmember))?     $jsonObj->addmember : [];

            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $this->b_factory_id = isset($wp_work->b_factory_id)? $wp_work->b_factory_id : 0;
            $this->e_project_id = isset($wp_work->e_project_id)? $wp_work->e_project_id : 0;
            $this->aproc        = isset($wp_work->aproc)? $wp_work->aproc : '';
            $charge_memo        = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';
            $this->dept1        = isset($wp_work->be_dept_id1)? $wp_work->be_dept_id1 : 0;
            $work_close         = isset($wp_work->isClose)? $wp_work->isClose : 'Y';

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //2.2 工作許可證不存在
            if($isSuc == 'Y' && !$this->aproc)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200213';// 該工作許可證不存在
            }
            //2.3 還在審查
            if($isSuc == 'Y' && $this->aproc == 'A')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200214';// 該工作許可證未審查
            }
            //2.4 審查不通過
            if($isSuc == 'Y' && $this->aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200232';// 審查不通過
            }
            //2.5 已經收完
            if($isSuc == 'Y' && $this->aproc == 'F')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200216';// 該工作許可證已收工
            }
            //2.6 已經停工
            if($isSuc == 'Y' && $this->aproc == 'C')
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
            //2.8 缺加入工單的人員資料
            if($isSuc == 'Y' && (!is_array($this->addmember) && !count($this->addmember)))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200281';// 缺加入工單的人員資料
            }else {
                foreach ($this->addmember as $val)
                {
                    $uid = isset($val->uid)? $val->uid : 0;
                    $iid = isset($val->iid)? $val->iid : 0;
                    if($uid && $iid)
                    {
                        if(wp_work_worker::isApplyExist($this->work_id,$uid,$iid,['A','P','R']))
                        {
                            $isSuc          = 'N';
                            $this->errCode  = 'E00200346';// 申請對象已存在或正在申請
                            $this->errParam1= User::getName($uid);
                            break;
                        } else {
                            $this->addmemberAry[$uid] = $iid;
                        }
                    }
                }
                if($isSuc == 'Y' && !count( $this->addmemberAry))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200281';// 缺加入工單的人員資料
                }
//                dd($this->addmember,$this->addmemberAry);
            }

            //3 登入檢核
            if($isSuc == 'Y')
            {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                //承攬商
                if($this->bc_type == 3) {
                    $isSuc = $this->addWorkPermitWorker2($this->work_id,$this->addmemberAry,$this->b_cust_id);
                } else {
                    $isSuc = $this->addWorkPermitWorker($this->work_id,$this->addmemberAry,$this->b_cust_id);
                }

                if($isSuc)
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
                } else {
                    $this->errCode   = 'E00200282'; //補人失敗，請聯絡管理者
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
