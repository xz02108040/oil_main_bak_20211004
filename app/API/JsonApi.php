<?php

namespace App\API;
//2019-09-03
ini_set('max_execution_time', 600);

use App\Http\Traits\BcNewsTrait;
use App\Http\Traits\VcBankTrait;
use App\Http\Traits\VcustTrait;
use App\Model\b_app_version;
use App\Model\sys_param;
use Auth;
use Lang;
use DateTime;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 * ApiToJson
 *
 */
class JsonApi
{

    //建構子：取得ＪＳＯＮ
    public function __construct($jsonObj,$clientIp) {
        if(!is_object($jsonObj)) return -1;
        $this->jsonObj   = $jsonObj;
        $this->clientIp  = $clientIp;
        $this->funcode   = $jsonObj->funcode;
        $this->rnum      = isset($jsonObj->rnum)? $jsonObj->rnum : rand(1111,9999);

        $t      = microtime(true);
        $micro  = sprintf("%06d",($t - floor($t)) * 1000000);
        $d      = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
        $this->now       = $d->format("Y-m-d H:i:s.u");
        $this->reply     = 'N';
        $this->errCode   = '';
        $this->startTime = $this->getProgramTime();
        $this->errtxt    = 'sys_api.';
        $this->tokenType = 'app';
    }

    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {

    }

    public function setErrTxt($ret)
    {
        $this->errtxt    = $ret;
    }

    public function setTokenType($ret)
    {
        $this->tokenType = $ret;
    }

    /**
     * 產生錯誤代碼
     * @param $code
     * @return array|string
     */
    public function genErrCdoeAry($code)
    {
        //錯誤訊息＿數值參數
        $err_val_ary1 = (isset($this->errParam1))? ['param1'=>$this->errParam1] : array();
        $err_val_ary2 = (isset($this->errParam2))? ['param2'=>$this->errParam2] : array();
        $err_val_ary = $err_val_ary1 + $err_val_ary2;
        //取得錯誤代碼
        if(!$code) $code = 'E00000';//如果沒有相關錯誤代碼
        $errMsg = Lang::get($this->errtxt.$code,$err_val_ary);

        //回傳格式（錯誤：「代碼」「訊息」）
        $ret = ['err'=> ['code'=>$code , 'msg'=> $errMsg]];

        return $ret;
    }

    public function getRet()
    {
        $ret = array();
        $ret['funcode'] = $this->funcode;
        $ret['rnum']    = $this->rnum;
        $ret['reply']   = $this->reply;
        $ret['systime'] = $this->now;
        $ret['version'] = sys_param::getParam('APP_VERSION',0);//版本 2019-12-10
        if($this->errCode)
        {
            $ret = $ret + self::genErrCdoeAry($this->errCode);
        }

        return $ret;
    }

    public function getProgramTime()
    {
        return microtime(true);
    }

    public function getRunTime()
    {
        $this->endTime = $this->getProgramTime();
        return ($this->startTime)? abs(($this->endTime - $this->startTime)) : 0;
    }

}
