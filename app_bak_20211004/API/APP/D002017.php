<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkImg;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Model\Emp\be_dept;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002017
 * 目的：回傳該進度是否已經完成
 *
 */
class D002017 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkPermitWorkImg,WorkPermitWorkProcessTopicOption;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait;
    use WorkPermitWorkOrderlineTrait;
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
            $this->work_process_id  = (isset($jsonObj->work_process_id))?   $jsonObj->work_process_id : '';
            $isExistToken           = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work                = wp_work::getData($this->work_id);

            if(!isset($wp_work->wp_permit_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200236';// 工作許可證不存在
            } else {

                $this->permit_id        = $wp_work->wp_permit_id;
                $aproc                  = $wp_work->aproc;
                $charge_memo            = $wp_work->charge_memo;
                $isClose                = $wp_work->isClose;
                $listData               = wp_work_list::getData($this->work_id);

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
                    $this->errCode  = 'E00200217';// 該工作許可證已停工
                }
                //2.6 已經作廢
                if($isSuc == 'Y' && $isClose === 'Y')
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200237';// 該工作許可證已作廢
                    $this->errParam1= $charge_memo;// 該工作許可證已停工
                }

                if(isset($listData->id))
                {
                    $this->list_id              = $listData->id;
                    $list_aproc                 = $listData->aproc;
                    $this->workerAry            = [$wp_work->supply_worker,$wp_work->supply_safer];
                    $this->supply_worker        = $wp_work->supply_worker;
                    $this->supply_safer         = $wp_work->supply_safer;
                    $this->dept1                = $wp_work->be_dept_id1;
                    $this->dept2                = $wp_work->be_dept_id2;
                    $this->dept3                = $wp_work->be_dept_id3;
                    $this->dept4                = $wp_work->be_dept_id4;
                    $this->dept5                = be_dept::getParantDept($this->dept1);
                    $this->work_status          = $listData->work_status;
                    $this->sub_status           = $listData->work_sub_status;
                    $this->wp_work_process_id   = $listData->wp_work_process_id;
                    $this->last_work_process_id = $listData->last_work_process_id;
                    //$this->process_id           = wp_work_process::getProcess($this->work_process_id);
                    //if(!$this->work_process_id && !$this->process_id) $this->process_id = 1;
                    //$this->target           = wp_permit_process_target::getTarget($this->permit_id,$this->process_id);
                    //人員資料
                    //list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::genPermitTarget($this->b_cust_id,$this->bc_type,$this->workerAry,$this->target,$this->supply_worker,$this->supply_safer,$this->dept1,$this->dept2,$this->dept3,$this->dept4,$this->dept5);


                    //2.7 程序異常:尚未啟動
                    if($isSuc == 'Y' && !in_array($list_aproc,['P','R','O']))
                    {
                        $this->errCode  = 'E00200223';// 尚未啟動
                    } else {
                        //已經啟動
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    }
                } else {
                    $this->errCode  = 'E00200223';// 尚未啟動
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
            //如果跟目前的進度一樣，代表 沒有
            if($this->wp_work_process_id == $this->work_process_id)
            {
                $statuc = 'N';
                $statuc_memo = Lang::get('sys_base.base_10936');
            } elseif($this->last_work_process_id == $this->work_process_id)
            {
                $statuc = 'Y';
                $statuc_memo = Lang::get('sys_base.base_10935');
            } else {
                $statuc = 'O';
                $statuc_memo = Lang::get('sys_base.base_10937');
            }
            $ret['status']          = $statuc;
            $ret['status_memo']     = $statuc_memo;

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
