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
 * D002127 [??????]-??????????????????????????????.
 * ??????????????? ??????????????????????????????.
 *
 */
class D002127 extends JsonApi
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
                $isClose            = $wp_work->isClose;

                //2.1 ??????/??????????????????
                if($isSuc == 'Y' &&!isset($isExistToken->token))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200101';// ???????????????
                }
                //2.2 ????????????????????????
                if($isSuc == 'Y' && !$aproc)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// ???????????????????????????
                }
                //2.3 ????????????
                if($isSuc == 'Y' && $aproc == 'A')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200214';// ??????????????????????????????
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
            }

            //3 ????????????
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if($this->bc_type)
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
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
            $ret['data'] = $this->getWorkOrderCheckRecord2List($this->work_id);
        }
        //????????????
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
