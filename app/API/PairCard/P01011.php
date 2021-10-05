<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_supply_paircard;
use Auth;
use Lang;
use Session;
/**
 * P01011
 * 印卡模組_大林_承攬商成員印卡白名單
 *
 */
class P01011 extends JsonApi
{
    use DoorTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        if( $clientIp == '::1') $clientIp = '127.0.0.1';
        //參數
        $this->tokenType= 'paricard';
        $this->errCode  = 'E0100101';//來源未授權
        $this->reply    = 'N';
        $this->token    = sys_param::getParam('CARD_API_TOKEN');

        $token              = (isset($jsonObj->token))?  $jsonObj->token : ''; //帳號
        $this->b_supply_id  = (isset($jsonObj->sid))?     $jsonObj->sid : ''; //帳號
        //授權來源
        if($token == $this->token)
        {
            if(!view_supply_paircard::isSupplyExist($this->b_supply_id,'N'))
            {
                $this->errCode  = 'E00300102';//該承攬商查無須配卡的成員
            } else {
                $this->errCode = '';
                $this->reply = 'Y';
            }
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
            $ret['data']   = view_supply_paircard::getSupplyMemberSelect($this->b_supply_id,'N',1);//承攬商名單

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
