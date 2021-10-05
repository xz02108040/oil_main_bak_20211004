<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Lib\LogLib;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_supply_paircard;
use App\Model\View\view_used_rfid;
use Auth;
use Lang;
use Session;
/**
 * P01012
 * 印卡模組_大林_承攬商成員基本資料
 *
 */
class P01012 extends JsonApi
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
        $this->b_cust_id    = (isset($jsonObj->uid))?     $jsonObj->uid : ''; //帳號
        //授權來源
        if($token == $this->token)
        {
            if(!view_supply_paircard::isExist($this->b_cust_id))
            {
                $this->errCode  = 'E00300103';//該承攬商成員查無須配卡
            }elseif(view_supply_paircard::isLock($this->b_cust_id))
            {
                $this->errCode  = 'E00300104';//該承攬商成員正在印卡中(鎖定)，不得重複選擇!
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
            //鎖定該成員
            LogLib::setLogPairCardLock($this->b_supply_id,$this->b_cust_id,'Y','1000000001');
            //該成員列印資料
            $ret['data']   = view_supply_paircard::getInfo($this->b_cust_id,1);
            $ret['memo']   = (view_used_rfid::isSupplyOverPairCardMaxNum($this->b_supply_id))? \Lang::get('sys_api.E00300106') : '';

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
