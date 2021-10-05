<?php

namespace App\API\APP;

use App\Http\Traits\Push\PushTraits;
use App\Lib\FcmPusherLib;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D003010 .
 * 目的：中介推播
 *
 */
class D003010 extends JsonApi
{
    use PushTraits;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        //參數
        $isSuc    = 'Y';
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        $this->tokenType    = 'app';     //ＡＰＩ模式：ＡＰＰ
        $this->errCode      = 'E00004';//格式不完整

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->data         = (isset($jsonObj->data))? $jsonObj->data : ''; //工程案件
            $app_key            = config('mycfg.app_key_20191218');
            //2.1 帳號/密碼不可為空
            if($token != $app_key)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00300101';// 不合法使用者
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $pushData = $this->data;
                $uid        = isset($pushData->uid)? $pushData->uid : '';
                $token      = isset($pushData->token)? $pushData->token : '';
                $type       = isset($pushData->type)? $pushData->type : '';
                $title      = isset($pushData->title)? $pushData->title : '';
                $body       = isset($pushData->body)? $pushData->body : '';

                $this->msg  = FcmPusherLib::pushSingleDevice($uid,$token,$type,'✪'.$title,$body);

                $this->reply     = 'Y';
                $this->errCode   = '';
            }
        }

        //2. 產生ＪＳＯＮ Ａrray
        $ret = $this->genReplyAry();
        //3. 回傳json 格式
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
            $ret['msg'] = $this->msg;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
