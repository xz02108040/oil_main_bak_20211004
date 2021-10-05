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
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_log_door_today;
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
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002104 [工作許可證]-啟動／收工／延長／暫停，取得對應階段之檢核項目.
 * 目的：啟動／收工／延長／暫停，取得對應階段之檢核項目.
 *
 */
class D002104 extends JsonApi
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
        $max_allow_time     = strtotime(date('Y-m-d 23:00:00'));
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?            $jsonObj->id : '';
            $this->isOffWork    = (isset($jsonObj->isOffWork))?     $jsonObj->isOffWork : '';
            $this->isReWork     = (isset($jsonObj->isReWork))?      $jsonObj->isReWork : '';
            $this->isShowTest   = (isset($jsonObj->isShowTest))?     $jsonObj->isShowTest : '';//TEST
            $this->isPatrol     = (isset($jsonObj->isPatrol) && in_array($jsonObj->isPatrol,['Y','N']))?        $jsonObj->isPatrol : 'N';
            $this->isPause      = (isset($jsonObj->isPause) && in_array($jsonObj->isPause,['Y','N']))?  $jsonObj->isPause : 'N';
            $this->isStop       = (isset($jsonObj->isStop) && in_array($jsonObj->isStop,['Y','N']))?            $jsonObj->isStop : 'N';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $this->shift_id     = isset($wp_work->wp_permit_shift_id)? $wp_work->wp_permit_shift_id : 0;
            $this->aproc        = isset($wp_work->aproc)? $wp_work->aproc : '';
            $charge_memo        = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';
            $this->dept1        = isset($wp_work->be_dept_id1)? $wp_work->be_dept_id1 : 0;
            $supply_worker      = wp_work_worker::getSelect($this->work_id,1,0,0);
            $supply_safer       = wp_work_worker::getSelect($this->work_id,2,0,0);
            $this->workerAry    = array_merge($supply_worker,$supply_safer);

            $work_close         = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            //dd([$wp_work,$listData,$this->process_id,$this->target]);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //2.2 工作許可證不存在
            if($isSuc == 'Y' && !$this->aproc)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200213';// 該工作許可證不存在
            }
            //2.3 還在審查
            if($isSuc == 'Y' && $this->aproc == 'A')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200214';// 該工作許可證未審查
            }
            //2.4 審查不通過
            if($isSuc == 'Y' && $this->aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200232';// 審查不通過
            }
            //2.5 已經收完
            if($isSuc == 'Y' && $this->aproc == 'F')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200216';// 該工作許可證已收工
            }
            //2.6 已經停工
            if($isSuc == 'Y' && $this->aproc == 'C')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200237';// 該工作許可證已停工
                $this->errParam1  = $charge_memo;// 該工作許可證已停工
            }
            //2.7 已經作廢
            if($isSuc == 'Y' && $work_close == 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200217';// 該工作許可證已作廢
            }
            //2.8 申請暫停，僅限施工階段
            if($isSuc == 'Y' && $this->isPause == 'Y' && $this->aproc != 'R')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200286';// 申請暫停，僅限施工階段
            }
            //2.9 申請暫停，僅限16:00
            if($isSuc == 'Y' && $this->isPause == 'Y' && time() > $max_allow_time)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200331';// 申請暫停，限定16:00之前
            }
            //2.10 申請暫停，僅限16:00
            if($isSuc == 'Y' && $this->isPause == 'Y' && $this->shift_id != 1)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200332';// 申請暫停，限定白天班
            }
            //2.11 申請復工，僅限16:00
            if($isSuc == 'Y' && $this->isReWork == 'Y' && time() > $max_allow_time)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200334';// 申請復工，限定16:00之前
            }
            //2.12 申請復工，僅限16:00
            if($isSuc == 'Y' && $this->isReWork == 'Y' && $this->shift_id != 1)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200335';// 申請復工，限定白天班
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
                    //所處部門
                    $dept                   = ($this->bc_type == 2)? view_dept_member::getDept($this->b_cust_id) : 0;
                    $myTitle                = ($this->bc_type == 2)? view_dept_member::getTitle($this->b_cust_id) : 0;

//                    dd($myTitle);

                    $listData               = wp_work_list::getData($this->work_id);
                    $this->list_id          = isset($listData->id)? $listData->id : 0;
                    $this->isLock           = isset($listData->isLock)? $listData->isLock : 'N';
                    $this->lock_user        = isset($listData->lock_user)? $listData->lock_user : 0;
                    $this->lock_user_name   = ($this->isLock == 'Y')? User::getName($this->lock_user) : '';
                    $this->lock_stamp       = ($this->isLock == 'Y')? substr($listData->lock_stamp,0,16) : '';

                    //目前階段ＩＤ
                    $this->work_process_id  = $listData->wp_work_process_id;
                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                    //上一個階段ＩＤ
                    $this->last_work_process_id  = $listData->last_work_process_id;
                    if(!$this->work_process_id && !$this->process_id) $this->process_id = 1;
                    //dd($this->list_id,$this->permit_id,$this->process_id,$this->target);

                    $ate_time_default   = sys_param::getParam('PERMIT_DEFAULT_ETIME');
                    $this->work_stime  = !is_null($listData->work_stime)? $listData->work_stime : '';
                    $this->work_etime    = !is_null($listData->eta_time)?   $listData->eta_time : $ate_time_default;
                    //人員資料
                    list($this->isOp,$this->myTarget,$this->myAppType) = HTTCLib::isTargetList($this->work_id,$this->b_cust_id);

                    if(!$this->isOp && in_array($this->aproc,['W']))
                    {
                        $this->errCode  = 'E00200228';// 無權限啟動
                    }
                    elseif(in_array($this->aproc,['W']) && !wp_work_worker::hasWorkerDoorIn($this->work_id))
                    {
                        $this->errCode  = 'E00200234';// 施工人員人數不足
                    }
                    elseif($this->process_id != 7 && $this->process_id !=22 && $this->isPause == 'Y')
                    {
                        $this->errCode  = 'E00200286';// 申請暫停，僅限施工階段
                    }
                    elseif($this->process_id != 24 && $this->isReWork == 'Y')
                    {
                        $this->errCode  = 'E00200337';// 申請復工，需該工單正處於暫停階段
                    }
                    elseif($this->process_id == 24 && $this->isReWork != 'Y')
                    {
                        $this->errCode  = 'E00200336';// 目前工單正處於暫停階段，需等待承攬商申請復工
                    }
                    elseif($this->process_id == 24 && $this->isReWork == 'Y' && !wp_work_worker::isReBackExist($this->work_id))
                    {
                        $this->errCode  = 'E00200338';// 申請復工，需該工單上原本的工安＆施工人員皆在廠內
                    }
                    //2.7 程序異常:不可以進入收工階段
                    elseif($this->isOffWork == 'Y' && !in_array($this->process_id,[7,24]))
                    {
                        $this->errCode  = 'E00200230';// 不可以進入收工階段
                    }
                    //2.7 程序異常:身份必須為 承商發起
                    elseif($this->isOffWork == 'Y' && !in_array($this->b_cust_id,$this->workerAry))
                    {
                        $this->errCode  = 'E00200229';// 該帳號無權限可啟動收工申請
                    }
                    //2.8 程序異常:身份必須為 承商發起
                    elseif($this->isOffWork == 'Y' && !in_array($this->b_cust_id,$this->workerAry))
                    {
                        $this->errCode  = 'E00200287';// 該帳號無權限可申請延長
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
                    //2.97 [施工階段]施工檢點，如果不是轄區，則不能要答案
                    elseif($this->isOp && $this->aproc == 'R' && $this->isPatrol != 'Y' && $dept && $this->dept1 != $dept && $myTitle != 4 )
                    {
                        $this->errCode  = 'E00200248';// 非轄區人員，無需定期氣體偵測環境作業
                    }
                    //2.98 [施工階段]巡邏，如果不是其它部門，則不能要答案
                    elseif($this->isOp && $this->aproc == 'R' && $this->isPatrol == 'Y' && !$dept )
                    {
                        $this->errCode  = 'E00200265';// 承攬商無需巡邏
                    }
                    //2.99 [鎖單]該階段　已有人簽核，且該人非你
                    elseif($this->isOp && $this->isLock == 'Y' && $this->lock_user != $this->b_cust_id )
                    {
                        $this->errCode  = 'E00200256';// 已有人簽核，且該人非你
                        $this->errParam1  = $this->lock_user_name;// 鎖定人員
                        $this->errParam2  = $this->lock_stamp;// 鎖定時間
                    }
                    //啟動程序
                    elseif($this->aproc == 'W')
                    {
                        //程序ＩＤ
                        //$this->process_id   = wp_permit_process::isStatusExist($this->permit_id,1,1,1);

                        $upAry = [];
                        $upAry['aproc']                 = 'P'; //啟動
                        //$upAry['wp_permit_process_id']  = $this->process_id;
                        $upAry['check_stime']           = date('Y-m-d H:i:s');
                        if($this->list_id && $this->setWorkPermitWorkOrderList($this->list_id,$upAry,$this->b_cust_id))
                        {
                            $this->reply     = 'Y';
                            $this->errCode   = '';

                            $listData               = wp_work_list::getData($this->work_id);
                            $this->work_process_id  = $listData->wp_work_process_id;
                            $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                            $this->nowProcess = wp_work_list::getNowProcessStatus($this->work_id);
                            //推播： 工作許可證啟動通知->承攬商：工地負責人/安衛人員
                            $this->pushToSupplyPermitWorkReady($this->work_id,$upAry['check_stime']);
                        } else {
                            $this->errCode  = 'E00200218';// 啟動失敗
                        }
                    } elseif($this->isStop == 'Y') {
                        $rule_reject_type = wp_permit_process::getRuleReject($this->process_id);
                        //進入申請收工，限承攬商
                        if($this->aproc != 'P')
                        {
                            $this->errCode  = 'E00200324';// 限定檢點階段
                        }
                        elseif($this->bc_type != 3)
                        {
                            $this->errCode  = 'E00200325';// 限定承攬商
                        }
                        else
                        {
                            if($rule_reject_type > 0)
                            {
                                if($this->setWorkPermitWorkOrderProcessSet($this->work_id, $this->list_id, $this->process_id, $this->work_process_id, ['Y','Y','','',''],0,$this->b_cust_id))
                                {
                                    $this->reply     = 'Y';
                                    $this->errCode   = '';

                                    $listData               = wp_work_list::getData($this->work_id);
                                    $this->work_process_id  = $listData->wp_work_process_id;
                                    $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                                    $this->nowProcess       = wp_work_list::getNowProcessStatus($this->work_id);
                                } else {
                                    $this->errCode  = 'E00200326';// 申請停工失敗，請聯絡管理者
                                }
                            } else {
                                $this->errCode  = 'E00200327';// 該階段不可申請停工
                            }
                        }
                    } elseif($this->isPause == 'Y' && $this->process_id == 7) {
                        //進入申請收工，限承攬商
                        if($this->bc_type != 3)
                        {
                            $this->errCode  = 'E00200329';// 限定承攬商
                        }
                        else
                        {
                            if($this->setWorkPermitWorkOrderProcessSet($this->work_id, $this->list_id, $this->process_id, $this->work_process_id, ['','','Y','',''],0,$this->b_cust_id))
                            {
                                $this->reply     = 'Y';
                                $this->errCode   = '';

                                $listData               = wp_work_list::getData($this->work_id);
                                $this->work_process_id  = $listData->wp_work_process_id;
                                $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                                $this->nowProcess       = wp_work_list::getNowProcessStatus($this->work_id);
                            } else {
                                $this->errCode  = 'E00200327';// 申請暫停失敗，請聯絡管理者
                            }
                        }
                    } elseif($this->isOffWork == 'Y') {
                        //進入收工階段
                        if($this->setWorkPermitWorkOrderProcessSet($this->work_id,$this->list_id,$this->process_id,$this->work_process_id,['N','','',$this->isOffWork,''],0,$this->b_cust_id))
                        {
                            //已經啟動
                            $this->reply     = 'Y';
                            $this->errCode   = '';

                            $listData               = wp_work_list::getData($this->work_id);
                            $this->work_process_id  = $listData->wp_work_process_id;
                            $this->process_id       = wp_work_process::getProcess($this->work_process_id);
                            $this->nowProcess = wp_work_list::getNowProcessStatus($this->work_id);
                        } else {
                            $this->errCode  = 'E00200233';// 啟動失敗
                        }
                    } else {
                        //目前階段狀態
                        $nowProcess = wp_work_list::getNowProcessStatus($this->work_id);
                        if(!$this->isOp)
                        {
                            $this->errCode  = 'E00200235';// 該:name1 階段<:name2 負責>
                            $this->errParam1  = $nowProcess['now_process'];// 階段
                            $this->errParam2  = $nowProcess['process_target2'];// 負責
                        } else {
                            $this->reply     = 'Y';
                            $this->errCode   = '';
                            $this->nowProcess= $nowProcess;
                        }
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

            //是否要顯示 工作許可證內容
            if($this->myAppType && !in_array($this->process_id,[7,8]))
            {
                //顯示 上一個階段 已經做過的答案
                $permit  = $this->getMyWorkPermitProcessTopicAns($this->work_id,$this->last_work_process_id,$this->apiKey);

            }
            //是否顯示 有無可以填寫的內容
            if($this->isOp)
            {
                $topic  = $this->getApiWorkPermitProcessTopic($this->permit_id,$this->process_id,$this->work_id,'',$this->myTarget,$this->isPatrol,1);
            }
            //為什麼 沒有題目《資格不符的原因》
            //$ret['msg']         = '';
//            if(!is_array($topic) || !count($topic))
//            {
                //$ret['msg']         = Lang::get('sys_api.E00200235',['name1'=>$nowProcess['now_process'],'name2'=>$nowProcess['process_target2']]);
//            }

            //目前階段狀態
            $ret['process_status']  = $this->nowProcess;
            //目前已填寫的<題目>內容
            $ret['history']         = $permit;
            //你登入身份 需要處理的內容<題目>
            $ret['topic']           = $topic;
            //這張工作許可證目前進行階段的紀錄ＩＤ
            $ret['work_process_id'] = $this->work_process_id;
            //是否可以重複作答
            $ret['isRepeat']        = wp_permit_process::getIsRepeat($this->process_id); //是否可以重複作答 2019-11-27 配合昱俊
            //是否可以重複作答
            $ret['isPatrol']        = ($this->aproc == 'R' && $this->bc_type == 2)? 'Y' : 'N'; //是否可以重複作答 2019-11-27 配合昱俊



            //這張工作許可證目前進行階段ＩＤ
            $ret['now_process_id']  = $this->process_id;

        }
        if($this->isShowTest)
        {
            $this->target           = wp_permit_process_target::getTarget($this->process_id);

            $tmp = [];
            $tmp['b_cust_id']       = $this->b_cust_id;
            $tmp['isOp']            = $this->isOp;
            $tmp['myAppType']       = $this->myAppType;
            $tmp['user']            = $this->b_cust_id;
            $tmp['process_id']      = $this->process_id;
            $tmp['target']          = $this->target;
            $tmp['myTarget']        = $this->myTarget;
            $tmp['isSupply']        = $isSupply;
            $tmp['isDept1']         = $isDept1;
            $tmp['isDept2']         = $isDept2;
            $tmp['work_id']         = $this->work_id;
            $tmp['permit_id']       = $this->permit_id;

            $ret['showtest']        = $tmp;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
