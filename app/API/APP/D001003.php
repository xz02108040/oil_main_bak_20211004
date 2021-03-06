<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Supply\SupplyMemberTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\WorkPermit\WorkCheckKindTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitIdentityTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorklineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Engineering\et_course;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_d;
use App\Model\Factory\b_factory_e;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_project_factory;
use App\Model\View\view_supply_user;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_permit_shift;
use App\Model\WorkPermit\wp_topic_type;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D001003 ??????.
 * ??????????????? ???????????????????????????
 *
 */
class D001003 extends JsonApi
{
    use BcustTrait,SupplyTrait,WorkPermitTrait,WorkPermitKindTrait,WorkPermitDangerTrait,WorkPermitWorkItemTrait;
    use WorkPermitIdentityTrait,SupplyMemberTrait,PushTraits;
    use WorkPermitProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicTrait,WorkPermitTopicOptionTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait,WorkPermitCheckTopicOptionTrait;
    use EngineeringDeptTrait,EngineeringFactoryTrait,FactoryDeviceTrait,EngineeringCarTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait,WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderlineTrait;
    use WorkPermitWorkItemCheckTrait,WorkPermitWorklineTrait,WorkCheckKindTrait;
    use EngineeringCarTrait;
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
        $this->be_dept_id   = 0;
        $this->b_supply_id  = 0;
        //????????????
        if(isset($jsonObj->token))
        {
            //1.1 ????????????
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //???????????????
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 ??????/??????????????????
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// ???????????????
            }
            //3 ????????????
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if(isset($this->bc_type))
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';

                    if($this->bc_type == 3)
                    {
                        $this->b_supply_id  = view_supply_user::getSupplyId($this->b_cust_id);

                    } else
                    {
                        $this->be_dept_id   = view_dept_member::getDept($this->b_cust_id);
                        $RootDept           = sys_param::getParam('ROOT_CHARGE_DEPT',1);
                        $isRootDept         = ($RootDept == $this->be_dept_id)? true : false;
                        $this->be_dept_id   = $isRootDept? 0 : $this->be_dept_id;
                    }

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
            //????????????
            $ret['search']          = [];
            //
            $ret['search']['door_mode'] = [['id'=>'M','name'=>'???????????????'],['id'=>'C','name'=>'???????????????']];
            //????????????:?????????
            $ret['search']['store']             = ($this->b_supply_id)? view_project_factory::getSupplyLocal($this->b_supply_id,0) : b_factory::getApiSelect(0);
            $ret['search']['be_dept']           = be_dept::getApiSelect(0,0);
            $ret['search']['b_factory_a']       = b_factory_a::getApiSelect(0,0);
            $ret['search']['b_factory_b']       = b_factory_b::getApiSelect(0,0);
            $ret['search']['b_factory_d']       = b_factory_d::getApiSelect(0,0);
            $ret['search']['b_factory_e']       = b_factory_e::getApiSelect(0,0);
            //????????????:????????????
            $ret['search']['project']           = e_project::getApiSelect($this->b_supply_id);//????????????
            $ret['search']['project2']          = e_project::getApiSelect($this->b_supply_id,$this->be_dept_id);//????????????
            $ret['search']['project_type']      = e_project_type::getApiSelect();//??????????????????
            $ret['search']['project_aproc']     = SHCSLib::getCode('ENGINEERING_APROC',1,1); //??????

            //????????????:?????????
            $ret['search']['supply']            = b_supply::getApiSelect($this->b_supply_id);//??????????????????
            //????????????:????????????
            $ret['search']['violation']         = e_violation::getApiSelect();
            $ret['search']['violation_law']     = e_violation_law::getApiSelect();
            $ret['search']['violation_type']    = e_violation_type::getApiSelect();
            $ret['search']['violation_punish']  = e_violation_punish::getApiSelect();
            //????????????:????????????
            $ret['search']['course']            = et_course::getApiSelect();
            //???????????????
            $ret['search']['shift']             = wp_permit_shift::getSelect(0,0,1); //??????
            $ret['search']['wp_permit_danger']  = SHCSLib::getCode('PERMIT_DANGER',1,1);
            $ret['search']['permit_aproc']      = SHCSLib::getCode('PERMIT_APROC',1,1);
            $ret['search']['extended_aproc']    = SHCSLib::getCode('EXTENDED_APROC',1,1);
            $ret['search']['tranuser_aproc']    = SHCSLib::getCode('TRANUSER_APROC',1,1);
            $ret['search']['wp_item']           = $this->getApiWorkPermitKind(1);
            $ret['search']['wp_danger']         = $this->getApiWorkPermitDanger(0,1,'N');
            $ret['search']['wp_check']          = $this->getApiWorkCheckKind();
            $ret['search']['wp_line']           = $this->getApiWorkPermitWorkLine();
            $ret['search']['wp_extened_aproc']  = SHCSLib::getCode('EXTENDED_APROC',1,1); //??????

        }
        //????????????
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
