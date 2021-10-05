<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Supply\SupplyTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitIdentityTrait;
use App\Http\Traits\WorkPermit\WorkPermitKindTrait;
use App\Http\Traits\WorkPermit\WorkPermitTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemTrait;
use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_type;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\Factory\b_factory;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_project_factory;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_topic_type;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D001009 登出.
 * 目的：註銷 推播ＩＤ
 *
 */
class D001009 extends JsonApi
{
    use BcustTrait,SupplyTrait,WorkPermitTrait,WorkPermitKindTrait,WorkPermitDangerTrait,WorkPermitWorkItemTrait;
    use WorkPermitIdentityTrait;
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
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = isset($this->b_cust->bc_type)? $this->b_cust->bc_type : '';

                if($this->bc_type)
                {
                    //Token失效
                    TokenLib::closeToken($this->b_cust_id,$this->tokenType);
                    //註銷推播ＩＤ
                    $this->closeMyPuserID($this->b_cust_id);

                    $this->reply     = 'Y';
                    $this->errCode   = '';
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
