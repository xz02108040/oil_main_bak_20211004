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
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Http\Traits\WorkPermit\WorkRPExtendedTrait;
use App\Http\Traits\WorkPermit\WorkRPTranUserDetailTrait;
use App\Http\Traits\WorkPermit\WorkRPTranUserTrait;
use App\Lib\HTTCLib;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_rp_extension;
use App\Model\WorkPermit\wp_work_rp_tranuser;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002132 [工作許可證]-工單轉單申請單.
 * 目的：監造審查.工單轉單申請單
 *
 */
class D002132 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,PushTraits;
    use WorkPermitWorkOrderlineTrait,WorkRPTranUserTrait,WorkRPTranUserDetailTrait;
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
        $today              = date('Y-m-d');
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $jsonData           = (isset($jsonObj->data))?          $jsonObj->data : '';
            $this->id           = (isset($jsonData[0]->id))?            $jsonData[0]->id : '';
            $this->charge_memo  = (isset($jsonData[0]->charge_memo))?   $jsonData[0]->charge_memo : '';
            $this->agree        = (isset($jsonData[0]->agree) && in_array($jsonData[0]->agree,['Y','N']))?  $jsonData[0]->agree : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            } elseif($isSuc == 'Y' && (!$this->id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200305';// 請先選擇申請單
            }elseif($isSuc == 'Y' && (!$this->agree))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200300';// 請告知是否同意還是不同意
            }elseif($isSuc == 'Y' && ($this->agree == 'N' && !$this->charge_memo))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200296';// 不同意，請填寫原因
            }

            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if($this->bc_type == 2)
                {
                    list($this->work_id,$this->apply_aproc,$this->charge_dept1)  = wp_work_rp_tranuser::getChargeInfo($this->id);
//                    dd($this->work_id,$this->apply_aproc,$this->charge_dept1,$this->charge_dept2);
                    $wp_work            = wp_work::getData($this->work_id);
                    $this->aproc        = isset($wp_work->aproc)? $wp_work->aproc : '';
                    $this->b_supply_id  = isset($wp_work->b_supply_id)? $wp_work->b_supply_id : 0;
                    $this->work_date    = isset($wp_work->sdate)? $wp_work->sdate : '';

                    if((!$this->aproc || !$this->work_date))
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200291';// 無此工單，請聯絡資訊處理
                    } elseif($isSuc == 'Y' && $this->aproc != 'R')
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200293';// 該工單並非在施工階段
                    } elseif($isSuc == 'Y' && $this->work_date != $today)
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200292';// 僅現今日施工階段的工單才能申請
                    }
//                    elseif($isSuc == 'Y' && $this->eta_time2 && (time() > $eta_time_stamp2))
//                    {
//                        $isSuc          = 'N';
//                        $this->errCode  = 'E00200306';// 該申請單已經超過時間
//                        $this->errParam1= $this->eta_time2;
//                    }

                    if($isSuc == 'Y')
                    {
                        $b_dept_id = view_dept_member::getDept($this->b_cust_id);
                        $isCharge1 = ($this->apply_aproc == 'A' && $b_dept_id == $this->charge_dept1)? true : false;

                        if(!$isCharge1)
                        {
                            if($this->apply_aproc == 'A') {
                                $this->errCode  = 'E00200307';// 目前階段為監造審查，你所屬部門非該單之監造部門
                            } elseif($this->apply_aproc == 'O') {
                                $this->errCode  = 'E00200298';// 該工單已審查通過
                            } elseif($this->apply_aproc == 'C') {
                                $this->errCode  = 'E00200299';// 該工單已審查不通過
                            } else {
                                $this->errCode  = 'E00200297';// 該工單您無權審查，請再確認
                            }
                        } else {
                            $INS = [];
                            $INS['charge_memo']    = $this->charge_memo;
                            if($isCharge1)
                            {
                                $INS['aproc']    = $this->agree == 'Y' ? 'O' : 'C';
                            }
//                            dd($this->id,$INS,$this->apply_aproc,$isCharge1,$isCharge2);
                            if($this->setWorkRPTranUserTrait($this->id,$INS,$this->b_cust_id))
                            {
                                $this->reply     = 'Y';
                                $this->errCode   = '';
                            } else {
                                $this->errCode  = 'E00200280';// 審查失敗
                            }
                        }
                    }
                } elseif($this->bc_type != 2)
                {
                    $this->errCode  = 'E00200313';// 不開放承攬商使用該功能
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

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
