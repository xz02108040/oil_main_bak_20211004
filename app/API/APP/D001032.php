<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Engineering\ViolationContractorTrait;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorInOutErrTrait;
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
 * D001032 違規儀表板<當周>.
 * 目的：取得人員違規<當周>
 *
 */
class D001032 extends JsonApi
{
    use BcustTrait,ViolationContractorTrait;
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
        list($this->sdate,$this->edate) = SHCSLib::getWeek();
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->violation_id = (isset($jsonObj->violation_id))? $jsonObj->violation_id : 0;
            $this->level        = (isset($jsonObj->level))? $jsonObj->level : 1;
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            if($isSuc == 'Y' && $this->level != 1 && !$this->violation_id)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200255';// 缺少違規事項參數
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
            $search = [0,0,$this->sdate,$this->edate,0,0,0,''];
            $ret['data']  = $this->getApiViolationContractorList($search,$this->level,2);

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
