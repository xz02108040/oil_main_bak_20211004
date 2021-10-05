<?php

namespace App\API\APP;

use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\Engineering\e_project;
use App\Model\sys_param;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D003001 .
 * 目的：取得 目前生效的 工程案號
 *
 */
class D003001 extends JsonApi
{
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
            $ret['project_no'] = e_project::getActiveProjectSelect(2,0,0,0);

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
