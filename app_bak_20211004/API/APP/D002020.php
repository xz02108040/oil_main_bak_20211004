<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkOrderCheckRecord1Trait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkImg;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_check_topic_a;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_process_topic;
use App\Model\WorkPermit\wp_work_topic_a;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002020
 * 目的：上傳 特定工作許可證內的指定題目圖片
 *
 */
class D002020 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkPermitWorkImg,WorkPermitWorkProcessTopicOption,WorkPermitProcessTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait,WorkOrderCheckRecord1Trait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
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
            $token                  = (isset($jsonObj->token))?             $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id          = (isset($jsonObj->id))?                $jsonObj->id : '';
            $this->work_process_id  = (isset($jsonObj->work_process_id))?   $jsonObj->work_process_id : 0;
            $this->topic_a_id       = (isset($jsonObj->topic_a_id))?        $jsonObj->topic_a_id : 0;
            $this->check_topic_a_id = (isset($jsonObj->check_topic_a_id))?  $jsonObj->check_topic_a_id      : 0;
            $this->ans              = (isset($jsonObj->ans))?               $jsonObj->ans : '';
            $isExistToken           = TokenLib::isTokenExist(0, $token,$this->tokenType);
            //工作許可證資料
            $wp_work                = wp_work::getData($this->work_id);
            if(!isset($wp_work->wp_permit_id) || !isset($isExistToken->b_cust_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200236';// 工作許可證不存在
            } else {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                $this->permit_id    = $wp_work->wp_permit_id;
                $aproc              = $wp_work->aproc;
                $isClose            = $wp_work->isClose;
                $charge_memo        = $wp_work->charge_memo;
                $listData           = wp_work_list::getData($this->work_id);
                $this->list_id      = $listData->id;
                $charge_user        = wp_work_process::getCharger($this->work_process_id);
                $this->workCheckId  = wp_work_process_topic::getWorkCheckId($this->work_id,$this->list_id,$this->topic_a_id);
                //2.1 帳號/密碼不可為空
                if($isSuc == 'Y' &&!isset($isExistToken->token))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200101';// 請重新登入
                }
                //2.2 工作許可證不存在
                if($isSuc == 'Y' && !$aproc)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// 該工作許可證不存在
                }
                //2.3 還在審查
                if($isSuc == 'Y' && $aproc == 'A')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200214';// 該工作許可證尚未審查
                }
                //2.5 已經收工
                if($isSuc == 'Y' && $aproc == 'F')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200216';// 該工作許可證已收工
                }
                //2.6 已經停工
                if($isSuc == 'Y' && $aproc == 'C')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200237';// 該工作許可證已停工
                    $this->errParam1= $charge_memo;// 該工作許可證已停工
                }
                //2.6 已經作廢
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200217';// 該工作許可證已作廢
                }
                //2.11 不是這階段負責人
//                if($isSuc == 'Y' && $charge_user != $this->b_cust_id)
//                {
//                    $isSuc          = 'N';
//                    $this->errCode  = 'E00200243';//
//                    $this->errParam1= User::getName($charge_user);// 負責人姓名
//                }
                //2.12 一班題目，不是圖片欄位
//                if($isSuc == 'Y' && $this->topic_a_id && !wp_permit_topic_a::isImgAns($this->topic_a_id))
//                {
//                    $isSuc          = 'N';
//                    $this->errCode  = 'E00200244';//
//                }
                //2.13 檢點單題目，不是圖片欄位
//                if($isSuc == 'Y' && $this->check_topic_a_id && !wp_check_topic_a::isImgAns($this->check_topic_a_id))
//                {
//                    $isSuc          = 'N';
//                    $this->errCode  = 'E00200253';//
//                }
                //2.14 該題目無作答記錄，不需上傳圖片
                if($isSuc == 'Y' && !wp_work_process_topic::isExist($this->work_id,$this->list_id,$this->topic_a_id))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200245';//
                }
                //2.15 該檢點單紀錄不存在
                if($isSuc == 'Y' && $this->check_topic_a_id && !$this->workCheckId)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200246';//
                }
                //2.16 未上傳圖片
                if($isSuc == 'Y' && strlen($this->ans) < 100)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200249';//
                }
                //TODO 檢查是否已經上傳過
                //2.20 該題目無作答記錄，不需上傳圖片
            }
//            dd($this->workCheckId);
            //3 登入檢核
            if($isSuc == 'Y')
            {

                if($this->check_topic_a_id)
                {
                    if($this->uploadWorkPermitTopicCheckImgRecord($this->work_id,$this->list_id,$this->work_process_id,$this->topic_a_id,$this->check_topic_a_id,$this->ans,$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200224';// 紀錄填寫失敗
                    }
                } else {
                    if($this->uploadWorkPermitTopicImgRecord($this->work_id,$this->list_id,$this->work_process_id,$this->topic_a_id,$this->ans,$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    } else {
                        $this->errCode  = 'E00200224';// 紀錄填寫失敗
                    }
                }

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
            //目前階段
            $ret['work_process_id'] = $this->work_process_id;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
