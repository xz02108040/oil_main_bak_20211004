<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\ViolationContractorTrait;
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
use App\Lib\HTTCLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_violation;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
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
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002123 [違規]-違規開立.
 * 目的：違規開立.
 *
 */
class D002123 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,PushTraits;
    use WorkPermitWorkOrderlineTrait,WorkRPExtendedTrait;
    use ViolationContractorTrait;
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
        $todayTime          = date('H:i:s');
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->user_id      = (isset($jsonObj->user_id))?       $jsonObj->user_id : '';
            $this->violation_id = (isset($jsonObj->violation_id))?  $jsonObj->violation_id : '';
            $this->e_project_id = (isset($jsonObj->e_project_id))?  $jsonObj->e_project_id : '';
            $this->supply_id    = (isset($jsonObj->supply_id))?     $jsonObj->supply_id : '';
            $this->work_id      = (isset($jsonObj->work_id))?       $jsonObj->work_id : '';
            $this->memo         = (isset($jsonObj->memo))?          $jsonObj->memo : '';
            $this->apply_date   = (isset($jsonObj->apply_date))?    $jsonObj->apply_date : $today;
            $this->apply_time   = (isset($jsonObj->apply_time))?    $jsonObj->apply_time : $todayTime;
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            } elseif($isSuc == 'Y' && (!$this->user_id || !is_numeric($this->user_id) || !User::isExist($this->user_id)))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200315';// 請選擇一個違規人員
            } elseif($isSuc == 'Y' && (!$this->violation_id  || !e_violation::isExist($this->violation_id)))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200316';// 請選擇一個違規事項
            } elseif($isSuc == 'Y' && (!$this->e_project_id && !$this->supply_id && !$this->work_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200317';// 請至少選擇一個違規參數(承攬商/案件/工單)
            } else {
                $data = view_door_supply_member::getData($this->user_id);
                if($isSuc == 'Y' && !isset($data->e_project_id))
                {
                    $isSuc            = 'N';
                    $this->errCode    = 'E00200347';// :name(查無此人)
                }
                if($isSuc == 'Y' && $this->e_project_id && $this->e_project_id != $data->e_project_id)
                {
                    $isSuc            = 'N';
                    $this->errCode    = 'E00200318';// 該違規人員:name1不屬於該工案:name2
                    $this->errParam1  = $data->name;
                    $this->errParam2  = e_project::getNo($this->e_project_id);
                }
                if($isSuc == 'Y' && $this->supply_id && $this->supply_id != $data->b_supply_id)
                {
                    $isSuc            = 'N';
                    $this->errCode    = 'E00200319';// 該違規人員:name1不屬於該承攬商:name2
                    $this->errParam1  = $data->name;
                    $this->errParam2  = b_supply::getName($this->supply_id);
                }
                if($isSuc == 'Y' && $this->work_id && !wp_work_worker::isExist($this->work_id,$this->user_id))
                {
                    $isSuc            = 'N';
                    $this->errCode    = 'E00200320';// 該違規人員:name1不屬於該工單:name2
                    $this->errParam1  = $data->name;
                    $this->errParam2  = wp_work::getNo($this->work_id);
                }

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
                    $upAry = [];

                    $upAry['e_violation_id']    = $this->violation_id;
                    $upAry['e_project_id']      = isset($data->e_project_id)? $data->e_project_id : 0;
                    $upAry['wp_work_id']        = isset($this->work_id)? $this->work_id : 0;
                    $upAry['b_supply_id']       = $data->b_supply_id;
                    $upAry['b_cust_id']         = $this->user_id;
                    $upAry['apply_date']        = $this->apply_date;
                    $upAry['apply_time']        = $this->apply_time;
                    $upAry['memo']              = $this->memo;
                    $upAry['apply_stamp']       = date('Y-m-d H:i:m',strtotime($this->apply_date.''.$this->apply_time));
//                    dd($upAry);
                    if($this->createViolationContractor($upAry,$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200321';// 開立失敗
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
