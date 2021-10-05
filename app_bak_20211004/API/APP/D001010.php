<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\View\view_user;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D001010 廠區人數統計儀表板.
 * 目的：取得廠區人數車輛儀表板
 *
 */
class D001010 extends JsonApi
{
    use BcustTrait,ReptDoorFactoryTrait,ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait,ReptDoorFactoryTrait;
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
            $this->store        = (isset($jsonObj->store))? $jsonObj->store : 0;
            $this->level        = (isset($jsonObj->level))? $jsonObj->level : 1;
            $this->supply       = (isset($jsonObj->supply))? $jsonObj->supply : 0;
            $this->mode         = (isset($jsonObj->mode))? $jsonObj->mode : 'M';
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
                $this->bc_type   = $this->b_cust->bc_type;

                if(isset($this->bc_type))
                {
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
            //承攬商
            if($this->bc_type == 3)
            {
                $this->supply = b_supply_member::getSupplyId($this->b_cust_id);
            }
            //職員
            else {

            }
            $ret['level']   = $this->level;
            $level          = ($this->level == 2)? 4 : 3;
            $ret['data']    = $this->genDoorInOutFactoryAppApi($this->store,0,$this->supply,$this->mode,$level);

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
