<?php

namespace App\API\Door;

use App\API\JsonApi;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 * place 測試
 *
 */
class place extends JsonApi
{
    use FactoryDeviceTrait;
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

        $account = (isset($jsonObj->uid))?  $jsonObj->uid : ''; //帳號
        $password  = (isset($jsonObj->pwd))?  $jsonObj->pwd : '';  //密碼
        //dd([$account,$password,$clientIp]);
        //1.1 來源正確<場地內碼，ＲＦＩＤ內碼，來源ＩＰ>
        list($this->localid,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);
        if($this->localid)
        {
            //來源正確
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
            $ret['place']['rows'] = $this->getApiFactoryDeviceReply($this->localid,2);//讀卡機設備

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
