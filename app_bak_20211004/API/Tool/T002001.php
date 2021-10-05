<?php

namespace App\API\Tool;

use App\Http\Traits\Factory\CarTrait;
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
/**
 * T002001 .
 * 目的：橋接 異系統[車輛白名單]
 *
 */
class T002001 extends JsonApi
{
    use CarTrait;
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
        $this->token        = sys_param::getParam('PUSH_API_TOKEN');
        $this->msg          = '';

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->data         = (isset($jsonObj->data))? $jsonObj->data : ''; //車牌
            //2.1 帳號/密碼不可為空
            if($token != $this->token)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00300101';// 不合法使用者
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                list($result,$suc,$err,$pass) = $this->toCreateEmpCar($this->data);
                $this->msg = Lang::get('sys_base.base_40401',['name2'=>$suc,'name3'=>$err,'name1'=>$pass]);
//                dd($result,$suc,$err,$pass);
                $this->reply     = ($result)? 'Y' : 'N';
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

        $ret['msg'] = $this->msg;
            //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
