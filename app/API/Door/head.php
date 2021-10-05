<?php

namespace App\API\Door;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Lib\LogLib;
use App\Model\Factory\b_factory_a;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use Log;

/**
 * 人員頭像
 *
 */
class head extends JsonApi
{
    use DoorTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        ini_set('max_execution_time', 900);
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        if( $clientIp == '::1') $clientIp = '127.0.0.1';
        //參數
        $this->tokenType= 'door';
        $this->errCode  = 'E0100101';//來源未授權
        
        $account   = (isset($jsonObj->uid))?        $jsonObj->uid : '';  //警衛室代碼
        $password  = (isset($jsonObj->pwd))?        $jsonObj->pwd : '';  //配對ＲＦＩＤ
        $id_code   = (isset($jsonObj->idcode))?     $jsonObj->idcode : '';  //讀卡機設備
        $isChange  = (isset($jsonObj->ischg))?      $jsonObj->ischg : '';  //讀卡機設備
        $this->code = rand(1111,9999);
        Log::info(Lang::get('sys_base.base_40402', ['name' => 'head', 'acc' => $account, 'pwd' => $password, 'code' => $this->code]));
        //dd([$account,$password,$clientIp]);
        //1.1 來源正確<場地內碼，ＲＦＩＤ內碼，來源ＩＰ>
        list($this->localid,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);
        if($this->localid)
        {
            //Log 紀錄
            $fid    = b_factory_a::getStoreId($this->localid);
            $img_at = ($isChange == 'Y')? LogLib::chkLogDoorHeadUp($fid,$this->localid,$clientIp) : -1;
            //dd([$isChange,$id_code,$img_at]);
            //來源正確
            list($this->whitelistAry,$this->rfidAry,$this->projectAry,$this->headAry) = $this->getWhiteList(1,2,$img_at);
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
            $ret['head']['rows']  = $this->headAry;//人員

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();
        Log::info(Lang::get('sys_base.base_40403', ['name' => 'head', 'difftime' => $ret['runtime'], 'code' => $this->code]));
        return $ret;
    }

}
