<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Lib\HTTCLib;
use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_permit_process_target;
use App\Model\WorkPermit\wp_permit_topic;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002014
 * 目的：
 *
 */
class D002014 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,PushTraits;
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
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->isOffWork    = (isset($jsonObj->isOffWork))?     $jsonObj->isOffWork : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $aproc              = isset($wp_work->aproc)? $wp_work->aproc : 0;
            $charge_memo        = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';

            $work_close         = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            //dd([$wp_work,$listData,$this->process_id,$this->target]);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
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
                $this->errCode  = 'E00200214';// 該工作許可證未審查
            }
            //2.4 審查不通過
            if($isSuc == 'Y' && $aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200232';// 審查不通過
            }
            //2.5 已經收完
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
                $this->errParam1  = $charge_memo;// 該工作許可證已停工
            }
            //2.7 已經作廢
            if($isSuc == 'Y' && $work_close === 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200217';// 該工作許可證已作廢
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if($this->bc_type)
                {
                    $listData               = wp_work_list::getData($this->work_id);
                    $this->list_id          = $listData->id;
                    $list_aproc             = $listData->aproc;
                    $isClose                = $listData->isClose;
                    $this->workerAry        = [$wp_work->supply_worker,$wp_work->supply_safer];
                    $this->supply_worker    = $wp_work->supply_worker;
                    $this->supply_safer     = $wp_work->supply_safer;
                    $this->dept1            = $wp_work->be_dept_id1;
                    $this->dept2            = $wp_work->be_dept_id2;
                    $this->dept3            = $wp_work->be_dept_id3;
                    $this->dept4            = $wp_work->be_dept_id4;
                    $this->dept5            = $wp_work->be_dept_id5;
                    $this->work_status      = $listData->work_status;
                    $this->sub_status       = $listData->work_sub_status;
                    $this->work_process_id  = $listData->wp_work_process_id;
                    $this->last_work_process_id  = $listData->last_work_process_id;
                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                    if(!$this->work_process_id && !$this->process_id) $this->process_id = 1;
                    $this->target           = wp_permit_process_target::getTarget($this->process_id);
                    $dept                   = view_dept_member::getDept($this->b_cust_id);

                    $ate_time_default   = sys_param::getParam('PERMIT_DEFAULT_ETIME');
                    $this->work_stime  = !is_null($listData->work_stime)? $listData->work_stime : '';
                    $this->work_etime    = !is_null($listData->eta_time)?   $listData->eta_time : $ate_time_default;
                    //人員資料
                    list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::genPermitTarget($this->b_cust_id,$this->bc_type,$this->workerAry,$this->target,$this->supply_worker,$this->supply_safer,$this->dept1,$this->dept2,$this->dept3,$this->dept4,$this->dept5);

                    //dd([$this->isOp,$this->myAppType,$this->list_id,$this->work_process_id,$this->process_id]);
                    if(!$this->isOp && in_array($list_aproc,['A']))
                    {
                        $this->errCode  = 'E00200228';// 無權限啟動
                    }
                    elseif(in_array($list_aproc,['A']) && !wp_work_worker::hasWorkerDoorIn($this->work_id))
                    {
                        $this->errCode  = 'E00200234';// 施工人員人數不足
                    }
                    elseif(in_array($list_aproc,['F']))
                    {
                        $this->errCode  = 'E00200216';// 已經收工
                    }
                    elseif(in_array($list_aproc,['C']))
                    {
                        $this->errCode  = 'E00200237';// 已經停工
                        $this->errParam1= $charge_memo;// 該工作許可證已停工
                    }
                    elseif($isClose === 'Y')
                    {
                        $this->errCode  = 'E00200217';// 已經作廢
                    }
                    //2.7 程序異常:不可以進入收工階段
                    elseif($this->isOffWork == 'Y' && !in_array($list_aproc,['R']))
                    {
                        $this->errCode  = 'E00200230';// 不可以進入收工階段
                    }
                    //2.7 程序異常:身份必須為 承商發起
                    elseif($this->isOffWork == 'Y' && !in_array($this->b_cust_id,$this->workerAry))
                    {
                        $this->errCode  = 'E00200229';// 該帳號無權限可啟動收工申請
                    }
                    //2.90 轄區主簽者 階段
//                    elseif (in_array($this->process_id,[11,13]))
//                    {
//                        list($isLostImg,$this->LostImgAry) = $this->getPermitWorkOrderProcessNeedImgUpload($this->work_id ,$this->list_id );
//                        if($isLostImg)
//                        {
//                            //$this->errCode  = 'E00200250';// 尚缺 :param1張圖片補上傳
//                            //$this->errParam1= $isLostImg;// 需要補上傳照片數量
//                        }
//                    }
                    //2.99 [施工階段]如果不是轄區，則不能要答案
                    elseif($this->isOp && $list_aproc == 'R' && $dept &&$this->dept1 != $dept )
                    {
                        $this->errCode  = 'E00200248';// 非轄區人員，無需定期氣體偵測環境作業
                    }
                    //啟動程序
                    elseif(!in_array($list_aproc,['P','R','O']))
                    {
                        //程序ＩＤ
                        $this->process_id   = wp_permit_process::isStatusExist($this->permit_id,1,1,1);

                        $upAry = [];
                        $upAry['aproc']                 = 'P'; //啟動
                        $upAry['wp_permit_process_id']  = $this->process_id;
                        $upAry['check_stime']           = date('Y-m-d H:i:s');
                        //dd([$this->list_id,$upAry]);
                        if($this->list_id && $this->setWorkPermitWorkOrderList($this->list_id,$upAry,$this->b_cust_id))
                        {
                            $this->reply     = 'Y';
                            $this->errCode   = '';

                            $listData               = wp_work_list::getData($this->work_id);
                            $this->work_status      = $listData->work_status;
                            $this->sub_status       = $listData->work_sub_status;
                            $this->work_process_id  = $listData->wp_work_process_id;
                            $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                            $this->target           = wp_permit_process_target::getTarget($this->process_id);
                            //推播： 工作許可證啟動通知->承攬商：工地負責人/安衛人員
                            $this->pushToSupplyPermitWorkReady($this->work_id,$upAry['check_stime']);
                        } else {
                            $this->errCode  = 'E00200218';// 啟動失敗
                        }
                    } elseif($this->isOffWork == 'Y'){
                        //進入收工階段
                        if($this->setWorkPermitWorkOrderProcessSet($this->work_id,$this->list_id,$this->process_id,$this->work_process_id,$this->isOffWork,['N','','','',''],0,$this->b_cust_id))
                        {
                            //已經啟動
                            $this->reply     = 'Y';
                            $this->errCode   = '';

                            $listData               = wp_work_list::getData($this->work_id);
                            $this->work_status      = $listData->work_status;
                            $this->sub_status       = $listData->work_sub_status;
                            $this->work_process_id  = $listData->wp_work_process_id;
                            $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                            $this->target           = wp_permit_process_target::getTarget($this->process_id);
                        } else {
                            $this->errCode  = 'E00200233';// 啟動失敗
                        }
                    } else {
                        //已經啟動
                        $this->reply     = 'Y';
                        $this->errCode   = '';
                    }
                } else {
                    $this->errCode  = 'E00200102';// 無法取得帳號資訊
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
            $isSupply = $isDept1 = $isDept2 = $isDept3 = $isDept4 = 0;
            $permit = $history = $topic = '';
            $ret['msg']         = '';
            $ret['isRepeat']    = wp_permit_process::getIsRepeat($this->process_id); //是否可以重複作答 2019-11-27 配合昱俊
            //目前階段狀態
            $nowProcess = wp_work_list::getNowProcessStatus($this->work_id);

            //是否要顯示 工作許可證內容
            if($this->myAppType && !in_array($this->process_id,[7,8]))
            {
                //顯示 上一個階段 已經做過的答案
                $permit  = $this->getMyWorkPermitProcessTopicAns($this->work_id,$this->last_work_process_id,$this->apiKey,$this->myTarget);
            }
            //是否顯示 有無可以填寫的內容
            if($this->isOp)
            {
                $topic  = $this->getApiWorkPermitProcessTopic($this->permit_id,$this->process_id,$this->work_id,'',$this->myTarget,'N',[$this->work_stime,$this->work_etime]);
            }
            //為什麼 沒有題目《資格不符的原因》
            if(!is_array($topic) || !count($topic))
            {
                //$ret['msg']         = Lang::get('sys_api.E00200235',['name1'=>$nowProcess['now_process'],'name2'=>$nowProcess['process_target2']]);
            }

            //目前階段狀態
            $ret['process_status']  = $nowProcess;
            //目前已填寫的<題目>內容
            $ret['permit']          = $permit;
            //你登入身份 需要處理的內容<題目>
            $ret['topic']           = $topic;
            //這張工作許可證目前進行階段的紀錄ＩＤ
            $ret['work_process_id'] = $this->work_process_id;
            //這張工作許可證目前進行階段ＩＤ
            $ret['now_process_id']  = $this->process_id;
            $ret['process_list']    = $this->getApiWorkPermitProcessTarget($this->permit_id,$this->work_id,$this->b_cust_id);


            $tmp = [];
            $tmp['isOp']            = $this->isOp;
            $tmp['myAppType']       = $this->myAppType;
            $tmp['user']            = $this->b_cust_id;
            $tmp['supply_worker']   = $this->supply_worker;
            $tmp['supply_safer']    = $this->supply_safer;
            $tmp['process_id']      = $this->process_id;
            $tmp['target']          = $this->target;
            $tmp['myTarget']        = $this->myTarget;
            $tmp['isSupply']        = $isSupply;
            $tmp['isDept1']         = $isDept1;
            $tmp['isDept2']         = $isDept2;
            $tmp['work_id']         = $this->work_id;
            $tmp['permit_id']       = $this->permit_id;

            $ret['test']    = $tmp;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
