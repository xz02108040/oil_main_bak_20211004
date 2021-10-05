<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_member;
use Auth;
use Lang;
use Session;
/**
 * P01001
 *
 */
class P01001 extends JsonApi
{
    use DoorTrait;
    /**
     * 顯示 回傳內容
     * @return json
     */
    public function toShow() {
        $jsonObj  = $this->jsonObj;
        $clientIp = $this->clientIp;
        if( $clientIp == '::1') $clientIp = '127.0.0.1';
        //參數
        $this->tokenType= 'paricard';
        $this->errCode  = 'E0100101';//來源未授權
        $this->reply    = 'N';
        $this->token    = sys_param::getParam('CARD_API_TOKEN');

        $token   = (isset($jsonObj->token))?  $jsonObj->token : ''; //帳號
        //授權來源
        if($token == $this->token)
        {
            $data = [];
            $this->errCode = '';
            $this->reply = 'Y';
        } else {
            $this->errCode  = 'E00300101';//來源未授權
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
            $lastno = sprintf("%08d", (sys_param::getParam('CARD_PAIR_LAST_NUMBER')+1));
            //回傳內容
            $ret['title']  = [['id'=>'uid','name'=>'承攬商編號'],['id'=>'name','name'=>'姓名'],['id'=>'memo1','name'=>'承攬商'],['id'=>'memo2','name'=>'備註1'],['id'=>'memo3','name'=>'備註2']];
            $ret['lastno'] = $lastno;//最後的編號
            $ret['no_head']= sys_param::getParam('CARD_NO_HEAD');//最後的編號
            $ret['data']   = b_supply_member::getAllPassMember();//人員

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
