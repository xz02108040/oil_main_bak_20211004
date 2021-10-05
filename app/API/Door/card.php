<?php

namespace App\API\DOOR;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use Log;
/**
 * card RFID & 人員
 *
 */
class card extends JsonApi
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
        $this->tokenType= 'door';
        $this->errCode  = 'E0100101';//來源未授權

        $account   = (isset($jsonObj->uid))?  $jsonObj->uid : ''; //帳號
        $password  = (isset($jsonObj->pwd))?  $jsonObj->pwd : '';  //密碼
        $this->code = rand(1111,9999);
        Log::info(Lang::get('sys_base.base_40402',['name' => 'card', 'acc' => $account, 'pwd' => $password, 'code' => $this->code]));
        //dd([$account,$password,$clientIp]);
        //1.1 來源正確<場地內碼，ＲＦＩＤ內碼，來源ＩＰ>
        list($this->localid,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);
        if($this->localid)
        {
            //來源正確
            list($this->whitelistAry,$this->rfidAry,$this->projectAry,$this->headAry) = $this->getWhiteList();
            $this->reply    = 'Y';
            $this->errCode  = '';
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
            $ret['member']['rows']  = $this->whitelistAry;//人員
            $ret['card']['rows']    = $this->rfidAry;//ＲＦＩＤ
            $ret['project']['rows'] = $this->projectAry;//工程案件

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();
        Log::info(Lang::get('sys_base.base_40403', ['name' => 'card', 'difftime' => $ret['runtime'], 'code' => $this->code]));
        return $ret;
    }

}
