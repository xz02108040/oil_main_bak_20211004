<?php

namespace App\API\Door;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\Report\ReptDoorCarInOutTodayTrait;
use App\Http\Traits\Report\ReptDoorMenInOutTodayTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Model\sys_param;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 *  出入紀錄
 *
 */
class import_door extends JsonApi
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
        $errCode = '';
        $this->tokenType            = 'door';
        $this->errCode              = 'E0100101';//來源未授權

        $account        = (isset($jsonObj->uid))?  $jsonObj->uid     : ''; //帳號
        $password       = (isset($jsonObj->pwd))?  $jsonObj->pwd     : '';  //密碼
        $this->id       = (isset($jsonObj->id))?   $jsonObj->id      : '';  //身分證
        $this->mode     = (isset($jsonObj->mode))? $jsonObj->mode    : 0;  //刷卡時間
        $this->time     = (isset($jsonObj->time))? $jsonObj->time    : '';  //刷卡時間
        $this->img      = (isset($jsonObj->img) && $jsonObj->img)?  $jsonObj->img     : '';  //刷卡照片
        $this->n        = (isset($jsonObj->n))?    $jsonObj->n       : '';  //刷卡照片
        $this->place    = (isset($jsonObj->place))?     $jsonObj->place       : '';  //刷卡照片
        $this->isdoor   = (isset($jsonObj->isdoor))?    $jsonObj->isdoor       : '';  //刷卡照片
        if(!in_array($this->mode,[1,2]))
        {
            $this->mode     = ($this->mode === 'I')? 1 : ($this->mode === 'O'? 2 : 0);
        }

        $this->logid    = 0;
        if($this->n == "test") dd([$account,$password,$clientIp,$jsonObj]);
        //1.1 來源正確<場地內碼，ＲＦＩＤ內碼，來源ＩＰ>
        //TODO IP現在不檢查
        list($this->storeid,$this->door_id,$this->door_name,$this->door_type) = CheckLib::isStoreDeviceToken($account,$password);
        if($this->storeid)
        {

            list($this->logid,$door_result,$door_result_kind,$door_result_name,$door_memo,$isOnline,$door_type,$door_data) = $this->createDoorInoutRecord($this->id,$this->storeid,$this->door_id,$this->mode,$this->time,$this->img,$this->isdoor);
            //dd([$this->id,$this->localid,$this->placeid,$this->mode,$this->time,$this->img,$errCode]);
            if($this->logid)
            {
                $this->reply            = 'Y';
                $this->errCode          = '';
                $this->door_result      = $door_result;
                $this->door_result_kind = $door_result_kind;
                $this->door_result_name = $door_result_name;
                $this->door_memo        = $door_memo;
                $this->isOnline         = $isOnline;
                $this->door_type        = $door_type;
                $this->door_data        = $door_data;
            } else {
                $this->errCode  = 'E0100202'; //進出入紀錄寫入異常
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

        $ret['msg']     = '';
        $ret['id']      = $this->n;//昱俊判斷使用的辨識ＩＤ
        $ret['no']      = $this->id;//身分證
        $ret['ww']      = $this->place;//昱俊判斷使用的設備識別碼
        $ret['logid']   = $this->logid;//Log_id
        if($this->reply == 'Y')
        {
            //回傳內容
            $ret['result']      = $this->door_result;//結果
            $ret['result_name'] = $this->door_result_name;//結果
            $ret['result_kind'] = $this->door_result_kind;//結果
            $ret['reply']       = $this->door_result;//結果
            //$ret['msg']         = $this->door_memo;//回傳

            $ret['isOnline']    = $this->isOnline;//
            $ret['count']       = 1;//
            $ret['fileCount']   = 1;//
            $ret['door_type']   = !is_null($this->door_type)? $this->door_type : 0;//
            $ret['data']        = $this->door_data;//
        } else {
            $ret['count']       = 0;//
            $ret['fileCount']   = 0;//
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
