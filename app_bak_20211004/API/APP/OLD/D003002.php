<?php

namespace App\API\APP;

use App\Http\Traits\Tmp\TmpProjectMemberTrait;
use App\Http\Traits\Tmp\TmpProjectTrait;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D003002 .
 * 目的：接收 目前介接資料庫的資料<工程案件，工程成員>
 *
 */
class D003002 extends JsonApi
{
    use TmpProjectTrait,TmpProjectMemberTrait;
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
            $this->project      = (isset($jsonObj->project))? $jsonObj->project : ''; //工程案件
            $this->member       = (isset($jsonObj->member))? $jsonObj->member : ''; //工程案件之成員
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
            list($isSuc11,$isSuc12,$isSuc13) = $this->createTmpProjectLoop($this->project,'2000000002');
            list($isSuc21,$isSuc22,$isSuc23) = $this->createTmpProjectMemberLoop($this->member,'2000000002');


            $ret['project'] = $this->project;
            $ret['member'] = $this->member;
            $ret['isSuc1'] = Lang::get('sys_base.base_40401',['name1'=>$isSuc13,'name2'=>$isSuc11,'name3'=>$isSuc12]);
            if(is_array($isSuc21))
            {
                $ret['isSuc2'] = $isSuc21;
            } else {
                $ret['isSuc2'] = Lang::get('sys_base.base_40401',['name1'=>$isSuc23,'name2'=>$isSuc21,'name3'=>$isSuc22]);
            }


        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
