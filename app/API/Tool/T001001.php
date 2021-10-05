<?php

namespace App\API\Tool;

use App\Http\Traits\Push\PushTraits;
use App\Lib\FcmPusherLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\sys_param;
use Auth;
use Lang;
use Session;
use UUID;
use Log;
/**
 * T001001 .
 * 目的：中介推播
 *
 */
class T001001 extends JsonApi
{
    use PushTraits;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        $this->code = rand(1111,9999);
        Log::info(Lang::get('sys_base.base_40404',['code' => $this->code]));

        //參數
        $isSuc    = 'Y';
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        $this->tokenType    = 'tool';     //ＡＰＩ模式：ＡＰＰ
        $this->errCode      = 'E00004';//格式不完整
        $this->token        = sys_param::getParam('PUSH_API_TOKEN');

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ

            //授權來源
            if($token == $this->token)
            {
                $this->errCode = '';
                $this->reply = 'Y';
            } else {
                $this->errCode  = 'E00300101';//來源未授權
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
            $ret['data'] = LogLib::getLogQueue();
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        Log::info(Lang::get('sys_base.base_40405', ['difftime' => $ret['runtime'], 'code' => $this->code]));
        return $ret;
    }

}
