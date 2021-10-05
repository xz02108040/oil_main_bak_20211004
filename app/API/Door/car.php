<?php

namespace App\API\DOOR;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
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
class car extends JsonApi
{
    use DoorTrait,ReptDoorMenInOutTodayTrait,ReptDoorCarInOutTodayTrait;
    use WorkPermitWorkerTrait,PushTraits;
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
        Log::info(Lang::get('sys_base.base_40402',['name' => 'car', 'acc' => $account, 'pwd' => $password, 'code' => $this->code]));

        //TEST
        $this->id       = (isset($jsonObj->car_no))?$jsonObj->car_no  : '';  //辨識依據(車牌)
        $this->mode     = (isset($jsonObj->mode))?  $jsonObj->mode    : 0;  //進廠離廠狀態(0:系統判斷,1:進廠,2:離廠)
        $this->time     = (isset($jsonObj->time))?  $jsonObj->time    : date('Y-m-d H:i:s');  //刷卡時間(如果未帶，視為接收時間)
        $this->img      = (isset($jsonObj->img) &&  $jsonObj->img)?  $jsonObj->img     : '';  //刷卡照片
        $this->logid    = 0;
        //dd([$account,$password,$clientIp]);

        if($this->id)
        {
            //TODO IP現在不檢查
            list($this->storeid,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);

            if($this->storeid)
            {
                if(!$this->id)
                {
                    $this->errCode  = 'E0100203'; //進出入紀錄寫入異常
                } else {

                    //
                    list($this->logid,$door_result,$door_result_name,$door_memo,$isOnline,$door_type,$door_data) = $this->createDoorInoutRecord($this->id,$this->storeid,$this->door_id,$this->mode,$this->time,$this->img,'C');

                    if($this->logid)
                    {
                        $this->reply    = 'Y';
                        $this->errCode  = '';
                    } else {
                        $this->errCode  = 'E0100202'; //進出入紀錄寫入異常
                    }
                }
            }
        } else {
            //1.1 來源正確<場地內碼，ＲＦＩＤ內碼，來源ＩＰ>
            list($this->factory_id,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);
            if($this->factory_id)
            {
                //來源正確
                list($this->supplyAry,$this->carnoAry,$this->updated_at) = $this->getCarWhiteList($this->factory_id);
                $this->reply    = 'Y';
                $this->errCode  = '';
            }
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
            if(!$this->id)
            {
                //回傳內容
                $ret['user']        = $this->supplyAry;//用戶(承攬商)
                $ret['car']         = $this->carnoAry;//車牌
                $ret['update_at']   = $this->updated_at;//最後異動時間
            }


        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();
        Log::info(Lang::get('sys_base.base_40403', ['name' => 'car', 'difftime' => $ret['runtime'], 'code' => $this->code]));
        return $ret;
    }

}
