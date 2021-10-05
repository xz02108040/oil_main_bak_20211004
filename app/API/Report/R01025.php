<?php

namespace App\API\Report;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorFactoryTrait;
use App\Http\Traits\Report\ReptDoorLogTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\Report\ReptPermit2Trait;
use App\Http\Traits\Report\ReptPermitTrait;
use App\Model\Factory\b_factory_d;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_wp_work;
use App\Model\WorkPermit\wp_work;
use Auth;
use Lang;
use Session;
/**
 * R01025
 *　[總廠]轄區施工中的工作許可證，施工中
 */
class R01025 extends JsonApi
{
    use DoorTrait,ReptPermitTrait,ReptPermit2Trait;
    use ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait,ReptDoorFactoryTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        if( $clientIp == '::1') $clientIp = '127.0.0.1';
        //參數
        $this->tokenType= 'rept';
        $this->errCode  = 'E0100101';//來源未授權
        $this->reply    = 'N';
        $this->token    = sys_param::getParam('REPORT_API_TOKEN');

        $token                  = (isset($jsonObj->token))?  $jsonObj->token : ''; //帳號
        $this->dept_id          = (isset($jsonObj->dept_id))?  $jsonObj->dept_id : 0;  //
        $this->searchid         = (isset($jsonObj->searchid))?  $jsonObj->searchid : 0;  //
        if(is_string($this->dept_id) && $this->dept_id != "0") $this->dept_id = explode(',',$this->dept_id);
        //授權來源
        if($token == $this->token && $this->dept_id)
        {
            $this->errCode = '';
            $this->reply = 'Y';
        } else {
            $this->errCode  = 'E00300101';//來源未授權
        }

        //2. 產生ＪＳＯＮ Ａrray
        $ret = $this->genReplyAry();
        //3. 回傳Array 格式
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
            //回傳內容
            $reptAry        =  $this->genPermitReptToday3($this->dept_id,$this->searchid);
            $ret['data']    =  $reptAry;

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
