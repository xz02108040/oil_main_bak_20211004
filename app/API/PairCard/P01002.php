<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\Factory\RFIDPairTrait;
use App\Http\Traits\Factory\RFIDTrait;
use App\Model\Factory\b_rfid;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_used_rfid;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 * P01002
 *
 */
class P01002 extends JsonApi
{
    use RFIDTrait,RFIDPairTrait;
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
        $this->b_cust_id= '1000000002';//來源未授權
        $this->reply    = 'N';
        $this->errCnt3Ary = $this->sucAry = [];
        $this->sucCnt   = $this->errCnt = $this->errCnt2 = $this->errCnt3 = $this->lossCnt = 0;
        $this->token    = sys_param::getParam('CARD_API_TOKEN');
        $token   = (isset($jsonObj->token))?  $jsonObj->token : ''; //token
        $data    = (isset($jsonObj->data))?  $jsonObj->data : '';  //回傳配卡結果

        //授權來源
        if($token == $this->token)
        {

            if(!count($data))
            {
                $this->errCode  = 'E00300102';//來源未授權
            } else {
                $this->reply = 'Y';
                $this->errCode = '';
                $this->sys_lastno = sprintf("%08d", (sys_param::getParam('CARD_PAIR_LAST_NUMBER')+1));
                $this->lastno  = 0;
                $no_head     = sys_param::getParam('CARD_NO_HEAD');
                $no_head_len = strlen($no_head);
                foreach ($data as $val)
                {
                    $getLastno = ($this->lastno)? $this->lastno + 1 : $this->sys_lastno;
                    $uid = isset($val->uid)? $val->uid : 0;
                    $cno = (isset($val->card_no) && $val->card_no)? $val->card_no : $getLastno;
                    $cno2= substr($cno,$no_head_len);
                    $cid = isset($val->card_code)? $val->card_code : '';
                    //dd($uid,$cno,$getLastno,$cid,$this->sys_lastno);
                    if($uid && $cno && $cid)
                    {
                        $isReap = 0;
                        if(b_rfid::isExist($cno))
                        {
                            $isReap = 1;
                            $this->errCnt3Ary[$isReap][] = $cno;
                        }
                        if(!$isReap && b_rfid::isExist(0,$cid))
                        {
                            $isReap = 2;
                            $this->errCnt3Ary[$isReap][] = $cid;
                        }
                        if(!$isReap)
                        {
                            list($rfid,$rfname,$rfcode) = view_used_rfid::isUserExist($uid);
                            if($rfid)
                            {
                                $isReap = 3;
                                $this->errCnt3Ary[$isReap][] = Lang::get('sys_api.E00300205',['name1'=>User::getName($uid),'name2'=>$rfname]);
                            }
                        }
                        //卡片沒有重複
                        if(!$isReap)
                        {
                            //20200630 配合昱俊回傳列印最後流水號
                            if($this->lastno < $cno2) $this->lastno = $cno2;

                            $INS1 = [];
                            $INS1['name']       = $cno;
                            $INS1['rfid_code']  = $cid;
                            $INS1['rfid_type']  = 5;
                            $rid = $this->createRFID($INS1,$this->b_cust_id);
                            if($rid)
                            {
                                $INS2 = [];
                                $INS2['b_rfid_id']  = $rid;
                                $INS2['b_cust_id']  = $uid;
                                $INS2['b_factory_id']  = 1;
                                $INS2['b_supply_id']= b_supply_member::getSupplyId($uid);
                                $pid = $this->createRFIDPair($INS2,$this->b_cust_id);
                                if($pid)
                                {
                                    $this->sucCnt++;
                                    $this->sucAry[] = $cno;
                                } else {
                                    $this->errCnt++;
                                }
                            } else {
                                $this->errCnt++;
                            }
                        } else {
                            //卡片流水號重複
                            $this->errCnt3++;
                        }
                    } else {
                        $this->lossCnt++;
                    }

                    //最後流水號
                    if($this->lastno)
                    {
                        sys_param::updateParam('CARD_PAIR_LAST_NUMBER',$this->lastno);
                    }
                }
            }



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
        $ret['msg'] = '';
        $ret['pair'] = '';
        if($this->reply == 'Y')
        {
            //回傳內容
            $sucMsg = Lang::get('sys_api.E00300201',['name1'=>$this->sucCnt,'name2'=>$this->errCnt]);
            if($this->errCnt3)
            {
                $err5strA = (isset($this->errCnt3Ary[1]) && count($this->errCnt3Ary[1]))? implode(',',$this->errCnt3Ary[1]) : '';
                $err5strB = (isset($this->errCnt3Ary[2]) && count($this->errCnt3Ary[2]))? implode(',',$this->errCnt3Ary[2]) : '';
                $err5strC = (isset($this->errCnt3Ary[3]) && count($this->errCnt3Ary[3]))? implode(',',$this->errCnt3Ary[3]) : '';
                $sucMsg .= ($err5strA)? Lang::get('sys_api.E00300202',['name1'=>$err5strA]) : '';
                $sucMsg .= ($err5strB)? Lang::get('sys_api.E00300203',['name1'=>$err5strB]) : '';
                $sucMsg .= ($err5strC)? Lang::get('sys_api.E00300206',['name1'=>$err5strC]) : '';
            }
            if($this->lossCnt)
            {
                $sucMsg .= Lang::get('sys_api.E00300204',['name1'=>$this->lossCnt]);
            }

            $ret['msg']  = $sucMsg;
            $ret['pair'] = $this->sucAry;
            $ret['err']['code'] = 'SUC';
            $ret['err']['msg'] = $sucMsg;
        }


        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
