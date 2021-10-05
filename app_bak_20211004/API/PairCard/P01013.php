<?php

namespace App\API\PairCard;

use App\API\JsonApi;
use App\Http\Traits\Bcust\BcustATrait;
use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\Factory\RFIDPairTrait;
use App\Http\Traits\Factory\RFIDTrait;
use App\Lib\SHCSLib;
use App\Model\Factory\b_rfid;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_supply_paircard;
use App\Model\View\view_used_rfid;
use Auth;
use Lang;
use Session;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
/**
 * P01013
 * 印卡模組_大林_上傳印卡紀錄
 */
class P01013 extends JsonApi
{
    use RFIDTrait,RFIDPairTrait,BcustATrait;
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
        $this->b_cust_id= '1000000000';//來源未授權
        $this->reply    = 'N';
        $this->errCnt3Ary = $this->sucAry = [];
        $this->sucCnt   = $this->errCnt = $this->errCnt2 = $this->errCnt3 = $this->lossCnt = 0;
        $this->token    = sys_param::getParam('CARD_API_TOKEN');
        $token   = (isset($jsonObj->token))?  $jsonObj->token : ''; //token
        $sid     = (isset($jsonObj->sid))?   $jsonObj->sid : 0;  //承攬商ID
        $data    = (isset($jsonObj->data))?  $jsonObj->data : '';  //回傳配卡結果

        //授權來源
        if($token == $this->token)
        {

            if(!is_array($data) || !count($data))
            {
                $this->errCode  = 'E00300105';//上傳資料中無配卡成功成員
            }elseif(!view_supply_paircard::isSupplyExist($sid))
            {
                $this->errCode  = 'E00300102';//該承攬商查無須配卡的成員
            } else {
                $this->reply = 'Y';
                $this->errCode = '';
                $this->sys_lastno = sprintf("%08d", (sys_param::getParam('CARD_PAIR_LAST_NUMBER')+1));
                $this->lastno  = 0;
                $rfid_id = 0;
                foreach ($data as $val)
                {
                    $uid = isset($val->uid)? $val->uid : 0;
                    $cno = ($this->lastno)? $this->lastno + 1 : $this->sys_lastno;
                    $cid = isset($val->card_code)? $val->card_code : '';
                    //dd($uid,$cno,$getLastno,$cid,$this->sys_lastno);
                    if($uid && $cno && $cid)
                    {
                        $isReap  = 0;
                        //2020-01-28 允許重複卡片(未配卡)
                        if($rfid_id = b_rfid::isExist(0,$cid))
                        {
                            //已配卡
                            if($view_rfid_id = view_used_rfid::isExistRfidCode($cid))
                            {
                                $isReap = 1;
                                $this->errCnt3Ary[$isReap][] = $cid;
                            }
                        }
                        if(!$isReap && !$rfid_id && b_rfid::isExist($cno))
                        {
                            $isReap = 2;
                            $this->errCnt3Ary[$isReap][] = $cno;
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
//                        dd($rfid_id,$cid,$isReap);
                        //卡片沒有重複
                        if(!$isReap)
                        {
                            if($this->lastno < $cno) $this->lastno = $cno;

                            //2-1. 頭像更新
                            if($uid && strlen($val->img) > 100)
                            {
                                //人頭像比例
                                $head_max_height = sys_param::getParam('USER_HEAD_HEIGHT',640);
                                $head_max_width  = sys_param::getParam('USER_HEAD_WIDTH',360);
                                //調查是否有無轉移角度
                                $Orientation = (isset($exif['IFD0']) && isset($exif['IFD0']['Orientation']))? $exif['IFD0']['Orientation'] : 0;
                                //圖片位置
                                $filepath = config('mycfg.user_head_path').date('Y/').$uid.'/';
                                $filename = $uid.'_head.jpg';
                                //轉換 圖片大小
                                if(SHCSLib::saveBase64ToImg($filepath,$filename,$val->img))
                                {
                                    $upAry = [];
                                    $upAry['head_img']  = $filepath.$filename;
                                    $this->setBcustA($uid,$upAry,$this->b_cust_id);
                                }
                            }
                            //2-2. 配卡紀錄
                            if(!$rfid_id)
                            {
                                $INS1 = [];
                                $INS1['name']       = $cno;
                                $INS1['rfid_code']  = $cid;
                                $INS1['rfid_type']  = 5;
                                $rfid_id = $this->createRFID($INS1,$this->b_cust_id);
                            }

                            if($rfid_id)
                            {
                                $INS2 = [];
                                $INS2['b_rfid_id']      = $rfid_id;
                                $INS2['e_project_id']   = view_supply_paircard::getProjectId($uid);
                                $INS2['b_cust_id']      = $uid;
                                $INS2['b_factory_id']   = 1;
                                $INS2['b_supply_id']    = $sid;
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
                $this->reply = 'N';
                $err5strA = (isset($this->errCnt3Ary[1]) && count($this->errCnt3Ary[1]))? implode(',',$this->errCnt3Ary[1]) : '';
                $err5strB = (isset($this->errCnt3Ary[2]) && count($this->errCnt3Ary[2]))? implode(',',$this->errCnt3Ary[2]) : '';
                $err5strC = (isset($this->errCnt3Ary[3]) && count($this->errCnt3Ary[3]))? implode(',',$this->errCnt3Ary[3]) : '';
                $sucMsg .= ($err5strA)? Lang::get('sys_api.E00300203',['name1'=>$err5strA]) : '';
                $sucMsg .= ($err5strB)? Lang::get('sys_api.E00300202',['name1'=>$err5strB]) : '';
                $sucMsg .= ($err5strC)? Lang::get('sys_api.E00300206',['name1'=>$err5strC]) : '';
            }
            if($this->lossCnt)
            {
                $this->reply = 'N';
                $sucMsg .= Lang::get('sys_api.E00300204',['name1'=>$this->lossCnt]);
            }

            $ret['reply']  = $this->reply;
            $ret['msg']    = $sucMsg;
        }


        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
