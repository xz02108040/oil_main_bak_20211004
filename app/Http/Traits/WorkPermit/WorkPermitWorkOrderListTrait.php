<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_supply_user;
use App\Model\WorkPermit\wp_permit_process;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_list;
use App\Model\WorkPermit\wp_work_process;
use App\Model\WorkPermit\wp_work_topic_a;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證-執行單/加班單
 *
 */
trait WorkPermitWorkOrderListTrait
{
    /**
     * 新增 工作許可證-執行單/加班單
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorkOrderList($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->wp_work_id)) return $ret;
        if(!empty(wp_work_list::isExist($data->wp_work_id, $data->pmp_kind))) return $ret;

        $INS = new wp_work_list();
        $INS->pmp_kind                  = $data->pmp_kind;
        $INS->wp_work_id                = $data->wp_work_id;
        $INS->work_status               = $data->work_status;
        $INS->work_sub_status           = $data->work_sub_status;
        $INS->wp_work_process_id        = $data->wp_work_process_id;
        $INS->apply_stamp               = date('Y-m-d H:i:s');
        $INS->apply_user                = $data->apply_user;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret)
        {
            //新增 第一筆 執行進度程序
            $tmp = [];
            $tmp['wp_work_id']          = $data->wp_work_id;
            $tmp['wp_work_list_id']     = $ret;
            $tmp['work_status']         = $data->work_status;
            $tmp['work_sub_status']     = $data->work_sub_status;
            $tmp['wp_permit_process_id']= $data->wp_permit_process_id;
            $this->createWorkPermitWorkOrderProcess($tmp,$mod_user);
        }
    }

    /**
     * 變更目前程序
     */
    public function setWorkPermitWorkOrderProcessSet($wp_work_id, $wp_work_list_id, $old_process_id,$work_process_id = 0, $rejectAry = ['N','','','',''],$lost_img_amt = 0,$mod_user = 1)
    {
        $ret = false;
        list($permit_id,$danger) = wp_work::getDataList($wp_work_id);
        list($pmp_kind,$old_status,$old_sub_status,$old_list_aproc) = wp_permit_process::getDataList($old_process_id);
        list($isReject,$reject_memo,$isPause,$isOffWork, $isStop) = $rejectAry;
        $rule_reject_type = wp_permit_process::getRuleReject($old_process_id);
        if(in_array($old_process_id, [9,23,25]) && $isReject && isset($isStop) && $isStop == 'Y') $rule_reject_type = 2; // [申請收工 (9) or 申請暫停 (23) or 申請復工 (25)] && 拒絕 && $isStop = 'Y' (轄區停工) => $rule_reject_type = 2 (進到停工階段) 否則執行階段預設的 rule_reject_type
        $now = date('Y-m-d H:i:s');
        $isSupply = view_supply_user::isSupply($mod_user);
        //$work_aproc = wp_work::isExist($wp_work_id);

//        dd('A',$isReject,$reject_memo,$rule_reject_type,$wp_work_list_id,$pmp_kind,$old_status,$old_sub_status,$old_process_id);
        // 申請暫停
        if($isPause == 'Y' && $isSupply) {
            //1-2 [承攬商]申請暫停－＞轄區審查
            $new_process_id = 22;
            //2-1 新增 第N筆 執行進度程序
            $UPD = [];
            $UPD['charge_user'] = $mod_user;
            $UPD['charge_memo'] = $reject_memo;
            $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
            //推播： 工作許可證申請暫停－＞通知轄區
            if($ret) $this->pushToSupplyPermitWorkPause2($wp_work_id,$mod_user,$reject_memo);
        }
        //申請復工成功
        elseif($old_process_id == 25 && !$isSupply && $isReject != 'Y') {
            //1-2 [承攬商]停工申請->監造審查
            $new_process_id = 7;
            //2-1 新增 第N筆 執行進度程序
            $UPD = [];
            $UPD['charge_user'] = $mod_user;
            $UPD['charge_memo'] = $reject_memo;
            $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
            //推播： 工作許可證復工通知->承攬商：工地負責人/安衛人員
            if($ret) $this->pushToSupplyPermitWorkReWork($wp_work_id,$mod_user);
        }
        //1. 判斷是否退件＆拒絕 2019-08-21
        elseif($isReject === 'Y' && strlen($reject_memo) && $rule_reject_type > 0)
        {
            //1-1 退回 施工階段 (收工)
            if($rule_reject_type == 1)
            {
                //退回 施工階段
                $new_process_id = 7;
                //3-1
                $UPD = [];
                $UPD['charge_user'] = $mod_user;
                $UPD['charge_memo'] = $reject_memo;
                $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
                //推播： 工作許可證收工申請退回->承攬商：工地負責人/安衛人員
                if($ret) $this->pushToSupplyPermitWorkBackToRun($wp_work_id,$mod_user,$reject_memo);
            } //1-2 退回 施工階段(申請暫停-轄區審查 失敗)
            elseif($rule_reject_type == 4)
            {
                //退回 施工階段
                $new_process_id = 7;
                //3-1
                $UPD = [];
                $UPD['charge_user'] = $mod_user;
                $UPD['charge_memo'] = $reject_memo;
                $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
                //推播： 工單退回施工階段
                if($ret) $this->pushToSupplyPermitWorkBackToRun2($wp_work_id,$mod_user,$reject_memo);
            } //1-2 退回 原本申請停工的前置階段
            elseif($rule_reject_type == 3)
            {
                //跳回兩個階段前
                $reBackAmt = ($old_process_id == 25)? 1 : 2;
                list($last_work_prcoess_id,$last_process_id) = wp_work_process::getLastProcessID($wp_work_id,$wp_work_list_id,$work_process_id,$reBackAmt);
                //退回 施工階段
                $new_process_id = $last_process_id;
                //3-1
                $UPD = [];
                $UPD['charge_user'] = $mod_user;
                $UPD['charge_memo'] = $reject_memo;
                $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
                if($ret && $old_process_id == 21)
                {
                    //推播： 工作許可證停工審查被拒->承攬商：工地負責人/安衛人員
                    $this->pushToSupplyPermitWorkBackToRun3($wp_work_id,$mod_user,$reject_memo);
                }
                if($ret && $old_process_id == 25)
                {
                    //推播： 工作許可證復工審查被拒->承攬商：工地負責人/安衛人員
                    $this->pushToSupplyPermitWorkBackToRun4($wp_work_id,$mod_user,$reject_memo);
                }

            } else { // 停工
                if($isSupply)
                {
                    //1-2 [承攬商]停工申請->監造審查
                    $new_process_id = 20;
                    //2-1 新增 第N筆 執行進度程序
                    $UPD = [];
                    $UPD['charge_user'] = $mod_user;
                    $UPD['charge_memo'] = $reject_memo;
                    $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
                    //推播： 工作許可證停工通知->承攬商：工地負責人/安衛人員
                    if($ret) $this->pushToSupplyPermitWorkStop2($wp_work_id,$mod_user,$reject_memo);
                } else {
                    //1-2 拒絕
                    $new_process_id = 0;
                    //2-1 新增 第N筆 執行進度程序
                    $UPD = [];
                    $UPD['charge_user'] = $mod_user;
                    $UPD['charge_memo'] = $reject_memo;
                    $UPD['aproc']       = 'C'; //拒絕
                    $UPD['check_etime'] = $now;
                    $UPD['etime'] = $now;
                    $ret = $this->setWorkPermitWorkOrderList($wp_work_list_id,$UPD,$mod_user);
                    //3-1
                    $ret = $this->setWorkPermitWorkOrderProcess($work_process_id,$UPD,$mod_user);
                    //推播： 工作許可證停工通知->承攬商：工地負責人/安衛人員
                    if($ret) $this->pushToSupplyPermitWorkStop($wp_work_id,$mod_user,$reject_memo);
                }
            }
//            dd('Ｂ',$isReject,$reject_memo,$rule_reject_type,$wp_work_list_id,$pmp_kind,$old_status,$old_sub_status,$old_process_id);

        } else {
//            dd('Ｃ',$isReject,$reject_memo,$rule_reject_type,$wp_work_list_id,$pmp_kind,$old_status,$old_sub_status,$old_process_id);
            //找到下一個程序
            $new_process_id = wp_permit_process::nextProcess($permit_id,$pmp_kind,$old_process_id,$danger,$wp_work_id,$wp_work_list_id,$isOffWork,$isPause);
        }

        //如果有大於零
        if($new_process_id > 0)
        {
            list($pmp_kind,$status,$sub_status,$list_aproc,$rule_app) = wp_permit_process::getDataList($new_process_id);

            //2-1 新增 第N筆 執行進度程序
            $tmp = [];
            $tmp['wp_work_id']          = $wp_work_id;
            $tmp['wp_work_list_id']     = $wp_work_list_id;
            $tmp['work_status']         = $status;
            $tmp['work_sub_status']     = $sub_status;
            $tmp['wp_permit_process_id']= $new_process_id;
            $tmp['lost_img_amt']        = $lost_img_amt;

            if($this->createWorkPermitWorkOrderProcess($tmp,$mod_user))
            {
                $isRun = $isApplyFinish = $isStopFinish = $isFinish = 0;
                $UPD = [];
                $UPD['aproc']               = $list_aproc;
                $UPD['work_status']         = $status;
                $UPD['work_sub_status']     = $sub_status;
                //檢點階段結束＝>施工階段
                if($old_list_aproc == 'P' && $list_aproc == 'R')
                {
                    //預計收工日期
                    $ate_time_topic_id  = sys_param::getParam('PERMIT_TOPIC_A_ID_ETIME');
                    $ate_time_default   = sys_param::getParam('PERMIT_DEFAULT_ETIME');
                    list($ate_time_topic_val1) = wp_work_topic_a::getTopicAns($wp_work_id,$ate_time_topic_id);
                    $ate_time_topic_val = ($ate_time_topic_val1)? $ate_time_topic_val1 : $ate_time_default;
                    $isRun = 1;
                    $UPD['check_etime'] = $now;
                    $UPD['work_stime']  = $now;
                    $UPD['eta_time']    = date('Y-m-d H:i:00',strtotime($ate_time_topic_val));
                }
                //施工階段結束＝>收工階段
                if($old_list_aproc == 'R' && $list_aproc == 'O')
                {
                    $UPD['work_etime']  = $now;
                    $UPD['close_stime'] = $now;
                }
                //2019-11-06
                if($new_process_id == 9)
                {
                    $isApplyFinish = 1;
                }
                //檢點階段提出停工結束＝>收工完成
                if($old_list_aproc == 'P' && $list_aproc == 'F')
                {
                    $isStopFinish       = 1;
                    $UPD['aproc']       = 'C';
                    $UPD['close_etime'] = $now;
                }
                //收工階段結束＝>收工完成
                if($old_list_aproc == 'O' && $list_aproc == 'F')
                {
                    $isFinish = 1;
                    $UPD['close_etime'] = $now;
                }

                //階段：進入 施工階段 <廣播>
                if($isRun)
                {
                    //通知　監造，該工單已啟動
                    $this->pushToSupplyPermitWorkStatus10($wp_work_id);
                    //通知，承攬商，該工單已啟動
                    $this->pushToSupplyPermitWorkStatus11($wp_work_id);
                    //釋放工負
                    $this->freeWorkPermitWorkerIdentityMen($wp_work_id,1);
                }
                //階段：進入 申請收工 <廣播>
                if($isApplyFinish)
                {
                    $this->pushToSupplyPermitWorkStatus20($wp_work_id);
                }
                //階段：進入 停工完成 <廣播>
                if($isStopFinish)
                {
                    list($reject_memo) = wp_work_topic_a::getTopicAns($wp_work_id,198);
                    $this->pushToSupplyPermitWorkStop($wp_work_id,$mod_user,$reject_memo);
                    $this->pushToSupplyPermitWorkStatus21($wp_work_id);
                }
                //階段：進入 收工完成 <廣播>
                if($isFinish)
                {
                    $this->pushToSupplyPermitWorkStatus21($wp_work_id);
                }
                $ret = $this->setWorkPermitWorkOrderList($wp_work_list_id,$UPD,$mod_user);
            }

            //階段：進入 啟動階段-承商回簽
            if($new_process_id == 2)
            {
                $this->pushToSupplyPermitWorkStatus12($wp_work_id);
            }
            //階段：進入 檢點階段-職安衛人員環境檢點
            if($new_process_id == 3)
            {
                $this->pushToSupplyPermitWorkStatus1($wp_work_id);
            }
            //階段：進入 轄區檢點
            if($new_process_id == 4)
            {
                $this->pushToSupplyPermitWorkStatus2($wp_work_id);
            }
            //階段：進入 聯繫者
            if(in_array($new_process_id,[5]))
            {
                $this->pushToSupplyPermitWorkStatus3($wp_work_id);
            }
            //階段：進入 複檢者
            if(in_array($new_process_id,[6]))
            {
                $this->pushToSupplyPermitWorkStatus3($wp_work_id,2);
            }
            //階段：進入 會簽
            if(in_array($new_process_id,[12]))
            {
                $this->pushToSupplyPermitWorkStatus4($wp_work_id);
            }
            //階段：進入 現場會勘-監工部門
            if(in_array($new_process_id,[14]))
            {
                $this->pushToSupplyPermitWorkStatus5($wp_work_id);
            }
            //階段：進入 現場會勘-承攬商
            if(in_array($new_process_id,[16]))
            {
                $this->pushToSupplyPermitWorkStatus6($wp_work_id);
            }
            //階段：進入 現場會勘-轄區部門
            if(in_array($new_process_id,[17]))
            {
                $this->pushToSupplyPermitWorkStatus7($wp_work_id);
            }
            //階段：進入 轄區主簽者
            if(in_array($new_process_id,[13]))
            {
                $this->pushToSupplyPermitWorkStatus8($wp_work_id);
            }
            //階段：進入 轄區經理
            if(in_array($new_process_id,[15]))
            {
                $this->pushToSupplyPermitWorkStatus9($wp_work_id);
            }
            //階段：申請暫停 審查
            if(in_array($new_process_id,[23]))
            {
                $this->pushToSupplyPermitWorkPause($wp_work_id,$mod_user,$reject_memo);
            }
            //階段：申請暫停 審查 通過－＞　申請復工
            if(in_array($new_process_id,[24]))
            {
                //人員釋放
                $this->updateWorkPermitWorkerMenOut($wp_work_id,0,[],$mod_user);
            }
            //階段：申請復工
            if(in_array($new_process_id,[25]))
            {
                //人員釋放
                $this->pushToSupplyPermitWorkReWork2($wp_work_id,$mod_user);
            }
            //階段：申請暫停 審查 通過－＞　申請復工
            if(in_array($old_process_id,[25]) && $new_process_id == 7)
            {
                //人員釋放
                $this->updateWorkPermitWorkerMeIn($wp_work_id);
            }
        } else {
            //程序結束
            if($new_process_id == -1)
            {
                //沒有下一階段
            }
        }
        return $ret;
    }

    /**
     * 修改 工作許可證-執行單/加班單
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkOrderList($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_list::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;

        //主階段
        if(isset($data->work_status) && $data->work_status > 0 && $data->work_status !== $UPD->work_status)
        {
            $isUp++;
            $UPD->work_status = $data->work_status;
        }
        //子階段
        if(isset($data->work_sub_status) && $data->work_sub_status > 0 && $data->work_sub_status !== $UPD->work_sub_status)
        {
            $isUp++;
            $UPD->work_sub_status = $data->work_sub_status;
        }
        //工作許可證指定之流程
        if(isset($data->wp_work_process_id) && $data->wp_work_process_id > 0 && $data->wp_work_process_id !== $UPD->wp_work_process_id)
        {
            $isUp++;
            //取得 上一個階段的程序ＩＤ 與 接下來的程序ＩＤ
            $now_process_id  = isset($data->now_permit_process_id)? $data->now_permit_process_id : 0;
            $last_process_id = wp_work_process::getProcess($UPD->wp_work_process_id);
            //進入收工作業的話
            $charge_user = ($now_process_id == 8 && $last_process_id == 7)? 0 : $mod_user;
            //更新舊的程序 ：結束時間
            $tmp = [];
            $tmp['charge_user']  = $charge_user;
            $tmp['etime']        = $now;
            $tmp['lost_img_amt'] = isset($data->lost_img_amt)? $data->lost_img_amt : 0;
            $this->setWorkPermitWorkOrderProcess($UPD->wp_work_process_id,$tmp,$mod_user);
            //更新成 新的程序
            $UPD->last_work_process_id = $UPD->wp_work_process_id;
            $UPD->wp_work_process_id   = $data->wp_work_process_id;
        }

        //預計完成時間
        if(isset($data->eta_time) && $data->eta_time && $data->eta_time !== $UPD->eta_time)
        {
            $isUp++;
            $UPD->eta_time = $data->eta_time;
        }
        //工作開始時間
        if(isset($data->check_stime) && $data->check_stime && $data->check_stime !== $UPD->check_stime)
        {
            $isUp++;
            $UPD->check_stime = $data->check_stime;
        }
        //工作結束時間
        if(isset($data->check_etime) && $data->check_etime && $data->check_etime !== $UPD->check_etime)
        {
            $isUp++;
            $UPD->check_etime = $data->check_etime;
        }
        //工作開始時間
        if(isset($data->work_stime) && $data->work_stime && $data->work_stime !== $UPD->work_stime)
        {
            $isUp++;
            $UPD->work_stime = $data->work_stime;
        }
        //工作結束時間
        if(isset($data->work_etime) && $data->work_etime && $data->work_etime !== $UPD->work_etime)
        {
            $isUp++;
            $UPD->work_etime = $data->work_etime;
        }
        //收工申請開始時間
        if(isset($data->close_stime) && $data->close_stime && $data->close_stime !== $UPD->close_stime)
        {
            $isUp++;
            $UPD->close_stime = $data->close_stime;
        }
        //收工審查結束時間
        if(isset($data->close_etime) && $data->close_etime && $data->close_etime !== $UPD->close_etime)
        {
            $isUp++;
            $UPD->close_etime = $data->close_etime;
        }
        //審查
        if(isset($data->aproc) && in_array($data->aproc,['B','P','R','O','F','C']) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_memo   = isset($data->charge_memo)? $data->charge_memo : '';
            $UPD->charge_sign   = isset($data->charge_sign)? $data->charge_sign : '';
            $UPD->wp_work_img_id= isset($data->wp_work_img_id)? $data->wp_work_img_id : '';
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            //新增程序:啟動（施工前檢點）
            if($data->aproc == 'P')
            {
                //新增 第一筆 執行進度程序
//                $tmp = [];
//                $tmp['wp_work_id']          = $UPD->wp_work_id;
//                $tmp['wp_work_list_id']     = $id;
//                $tmp['work_status']         = $UPD->work_status;
//                $tmp['work_sub_status']     = $UPD->work_sub_status;
//                $tmp['wp_permit_process_id']= $data->wp_permit_process_id;
//                $this->createWorkPermitWorkOrderProcess($tmp,$mod_user);

                //更新回去 主檔<開始執行時間>
                $tmp = [];
                $tmp['aproc']              = $data->aproc;
                $tmp['stime1']             = $now;
                $this->setWorkPermitWorkOrder($UPD->wp_work_id,$tmp,$mod_user);

                //人員在廠鎖定
                $this->updateWorkPermitWorkerMenReady($UPD->wp_work_id,$mod_user);
            }
            //變更程序:施工
            if($data->aproc == 'R')
            {
                //更新回去 主檔<啟動負責人，開始執行時間>
                $tmp = [];
                $tmp['aproc']       = $data->aproc;
                $tmp['eta_time']    = $UPD->eta_time;//預計收工時間
                $this->setWorkPermitWorkOrder($UPD->wp_work_id,$tmp,$mod_user);
                //開始施工時間
                $this->setWorkPermitWorkerMenWorkTime($UPD->wp_work_id,1,$mod_user);
            }
            //變更程序:收工作業
            if($data->aproc == 'O')
            {
                //更新回去 主檔<結束執行時間>
                $tmp = [];
                $tmp['aproc']              = $data->aproc;
                $this->setWorkPermitWorkOrder($UPD->wp_work_id,$tmp,$mod_user);
                //收工時間加上去
                $this->setWorkPermitWorkerMenWorkTime($UPD->wp_work_id,0,$mod_user);
            }
            //變更程序:施工結束/停工結束
            if($data->aproc == 'F' || $data->aproc == 'C')
            {
                //更新回去 主檔<結束執行時間>
                $tmp = [];
                $tmp['aproc']              = $data->aproc;
                $tmp['charge_user']        = $mod_user;
                $tmp['charge_memo']        = isset($data->charge_memo)? $data->charge_memo : '';
                $tmp['etime1']             = $now;
                $this->setWorkPermitWorkOrder($UPD->wp_work_id,$tmp,$mod_user);
                //人員釋放
                $this->updateWorkPermitWorkerMenOut($UPD->wp_work_id,1,[],$mod_user);
            }

        }
        //鎖定
        if(isset($data->isLock) && in_array($data->isLock,['Y','N']) && $data->isLock !== $UPD->isLock)
        {
            $isUp++;
            if($data->isLock == 'Y')
            {
                $UPD->isLock       = 'Y';
                $UPD->lock_user    = $mod_user;
                $UPD->lock_stamp   = $now;
            } else {
                $UPD->isLock = 'N';
                $UPD->lock_user    = 0;
            }
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證-執行單/加班單
     *
     * @return array
     */
    public function setApiWorkPermitOrderWorkTime($work_id,$work_list_id,$stime,$etime)
    {
        //
        $data           = wp_work_list::find($work_list_id);
        $data->eta_time = date('Y-m-d H:i:s',strtotime($etime));
        $data->save();

        //
        wp_work_topic_a::chgTopicAns($work_id,120,$stime);
        wp_work_topic_a::chgTopicAns($work_id,121,$etime);

        return 1;
    }


}
