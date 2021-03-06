<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\ViolationContractorTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkOrderCheckRecord2Trait;
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
 * D002126 [??????]-??????????????????????????????.
 * ??????????????? ??????????????????????????????.
 *
 */
class D002126 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,PushTraits;
    use WorkPermitWorkOrderlineTrait,WorkRPExtendedTrait;
    use WorkOrderCheckRecord2Trait;
    /**
     * ?????? ????????????
     * @return json
     */
    public function toShow() {
        //??????
        $isSuc    = 'Y';
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        $this->tokenType    = 'app';     //???????????????????????????
        $this->errCode      = 'E00004';//???????????????
        $today              = date('Y-m-d');
        $yesterday          = SHCSLib::addDay(-1);
        $now                = SHCSLib::getNow();
        //????????????
        if(isset($jsonObj->token))
        {
            //1.1 ????????????
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //???????????????
            $this->data         = (isset($jsonObj->data))?          (object)$jsonObj->data : [];
            $this->work_id      = (isset($jsonObj->work_id))?       $jsonObj->work_id : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $wp_work            = wp_work::getData($this->work_id);

            //2.1 ??????/??????????????????
            if(!isset($wp_work->wp_permit_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200236';// ????????????????????????
            } else {
                $this->permit_id    = $wp_work->wp_permit_id;
                $this->b_factory_id = $wp_work->b_factory_id;
                $aproc              = $wp_work->aproc;
                $sdate              = $wp_work->sdate;
                $isClose            = $wp_work->isClose;
                $charge_memo        = $wp_work->charge_memo;
                $shift_id           = $wp_work->wp_permit_shift_id;
                $listData           = wp_work_list::getData($this->work_id);

                //2.1 ??????/??????????????????
                if($isSuc == 'Y' &&!isset($isExistToken->token))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200101';// ???????????????
                }
                //2.2.1 ????????????????????????
                if($isSuc == 'Y' && !$aproc)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// ???????????????????????????
                }
                //2.2.2 ????????????????????????
                if($isSuc == 'Y' && !isset($this->data))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200323';// ??????????????????????????????
                }else {
                    $userAmt = 0;
                    foreach ($this->data as $val)
                    {
                        if(isset($val->user_id) && $val->user_id) $userAmt++;
                    }
                    if(!$userAmt)
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200323';// ??????????????????????????????
                    }
                }
                //2.2.3 ????????????????????????
                if($isSuc == 'Y' && !in_array($sdate,[$today]) && $shift_id == 1)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200247';// ????????????????????????
                }
                //2.2.4 ????????????????????????
                if($isSuc == 'Y' && !in_array($sdate,[$today,$yesterday]) && $shift_id == 2)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200247';// ????????????????????????
                }
                //2.3 ????????????
                if($isSuc == 'Y' && $aproc == 'A')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200214';// ??????????????????????????????
                }
                //2.5 ????????????
                if($isSuc == 'Y' && $aproc == 'F')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200216';// ???????????????????????????
                }
                //2.6 ????????????
                if($isSuc == 'Y' && $aproc == 'C')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200237';// ???????????????????????????
                    $this->errParam1= $charge_memo;// ???????????????????????????
                }
                //2.7 ????????????
                if($isSuc == 'Y' && $aproc === 'W')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200223';// ????????????
                }
                //2.8 ????????????
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200217';// ???????????????????????????
                }
                if(isset($listData->id))
                {
                    $this->list_id          = $listData->id;
                    $this->work_process_id  = $listData->wp_work_process_id;
                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                } else {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200223';// ????????????
                }
            }

            //3 ????????????
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if($this->bc_type == 3)
                {
                    $upAry = [];

                    $upAry['wp_work_id']        = $this->work_id;
                    $upAry['wp_work_list_id']   = isset($this->list_id)? $this->list_id : 0;
                    $upAry['wp_work_process_id']= isset($this->work_process_id)? $this->work_process_id : 0;
                    $upAry['wp_check_kind_id']  = isset($this->wp_check_kind_id)? $this->wp_check_kind_id : 2;
                    $upAry['user']              = $this->data;
//                     dd($upAry);
                    if($this->createWorkOrderCheckRecord2Group($upAry,$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200321';// ????????????
                    }
                } elseif($this->bc_type != 3)
                {
                    $this->errCode  = 'E00200295';// ?????????????????????????????????
                } else {
                    $this->errCode  = 'E00200102';// ????????????????????????
                }
            }
        }

        //2. ?????????????????? ???rray
        $ret = $this->genReplyAry();
        //3. ??????json ??????
        return $ret;
    }

    /**
     * ????????????????????????
     * @param $token
     * @return array
     */
    public function genReplyAry()
    {
        //????????????
        $ret = $this->getRet();

        if($this->reply == 'Y')
        {

        }
        //????????????
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
