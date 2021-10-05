<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\Factory\RFIDPairTrait;
use App\Http\Traits\Factory\RFIDTrait;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Factory\b_rfid;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_supply_paircard;
use App\Model\View\view_used_rfid;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 * P01014
 * 印卡模組_大林_列印解鎖
 */
class P01014 extends JsonApi
{
    use RFIDTrait,RFIDPairTrait,BcustATrait;
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
        $this->b_cust_id= '1000000002';//來源未授權
        $this->reply    = 'N';
        $this->token    = sys_param::getParam('CARD_API_TOKEN');
        $token   = (isset($jsonObj->token))?  $jsonObj->token : ''; //token
        $sid     = (isset($jsonObj->sid))?   $jsonObj->sid : 0;  //承攬商ID
        $uid     = (isset($jsonObj->uid))?   $jsonObj->uid : 0;  //承攬商ID

        //授權來源
        if($token == $this->token)
        {
            if(!view_supply_paircard::isLock($uid))
            {
                $this->errCode  = 'E00300107';//該承攬商成員正在印卡中(鎖定)，不得重複選擇!
            } elseif( LogLib::setLogPairCardLock($sid,$uid,'N',$this->b_cust_id) ) {
                $this->errCode = '';
                $this->reply = 'Y';
            } else {
                $this->errCode  = 'E00300108';//此人未被鎖定
            }
        } else {
            $this->errCode  = 'E00300101';//解鎖失敗
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
        $ret['msg'] = '';
        if($this->reply == 'Y')
        {
            //回傳內容
            $sucMsg = Lang::get('sys_api.E00300207');

            $ret['msg']  = $sucMsg;
        }


        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
