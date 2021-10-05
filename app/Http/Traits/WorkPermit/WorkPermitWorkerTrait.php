<?php

namespace App\Http\Traits\WorkPermit;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\Report\rept_doorinout_t;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member_l;
use App\Model\sys_param;
use App\Model\User;
use App\Model\WorkPermit\wp_permit_topic_a;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;

/**
 * 工作許可證_工作人員
 *
 */
trait WorkPermitWorkerTrait
{
    /**
     * 新增 工作許可證_工作人員
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createWorkPermitWorker($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->user_id) && $data->user_id) return $ret;
        $now = date('Y-m-d H:i:s');

        $INS = new wp_work_worker();
        $INS->wp_work_id                = $data->wp_work_id;
        $INS->user_id                   = $data->user_id;
        $INS->apply_type                = $data->apply_type;
        $INS->engineering_identity_id   = ($data->engineering_identity_id > 0)? $data->engineering_identity_id : 0;
        $INS->apply_user                = $mod_user;
        $INS->apply_stamp               = $now;
        $INS->isGuest                   = (in_array($data->isGuest,['Y','N']))? $data->isGuest : 'N';

        if($data->apply_type == 3)
        {
            $INS->isIn               = 'Y';
            $INS->isLock             = 'Y';
            $INS->in_time            = isset($data->in_time)? $data->in_time : 0;
            $INS->out_time           = isset($data->out_time)? $data->out_time : 0;
            $INS->door_total_time    = isset($data->door_total_time)? $data->door_total_time : 0;
            $INS->work_total_time    = isset($data->work_total_time)? $data->work_total_time : 0;
            $INS->door_stime1        = isset($data->door_stime1)? $data->door_stime1 : '';
            $INS->door_etime1        = isset($data->door_etime1)? $data->door_etime1 : '';
            $INS->door_stime         = isset($data->door_stime)? $data->door_stime : '';
            $INS->door_etime         = isset($data->door_etime)? $data->door_etime : '';
            $INS->work_stime         = isset($data->work_stime)? $data->work_stime : '';
            $INS->work_etime         = isset($data->work_etime)? $data->work_etime : '';
        }
        if(isset($data->aproc) && $data->aproc != 'A')
        {
            $INS->aproc                 = $data->aproc;
            $INS->charge_user           = $mod_user;
            $INS->charge_stamp          = $now;

        }

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        if($ret && isset($data->aproc) && $data->aproc == 'P')
        {
            //判斷是否在廠
            $store_id = wp_work::getStore($INS->wp_work_id);
            $today = date('Y-m-d');
            $doorObj = rept_doorinout_t::getData($store_id,$data->user_id,$today,1);
            if(isset($doorObj->wp_work_id) && $doorObj->wp_work_id == 0)
            {
                $INS->isIn          = 'Y';
                $INS->isLock        = 'Y';
                $INS->door_stime    = SHCSLib::getNow();
                $INS->door_stime1   = $doorObj->door_stamp;
                $INS->save();
                //如果現在沒有掛工單，且在廠
                $tmp = [];
                $tmp['wp_work_id']  = $INS->wp_work_id;
                rept_doorinout_t::where('id',$doorObj->id)->update($tmp);
            }
        }

        return $ret;
    }

    /**
     * 取代原本的 指定專業人員<工地負責人，安衛人員>
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkerMen($type = 1,$wid,$uid,$identity_id,$mod_user = 1)
    {
        if(!$uid) return false;

        //1. 先檢查是否跟原本紀錄一至
        if($type == 2)
        {
            $isExist = wp_work_worker::isExist($wid,$uid,$identity_id);
            //dd([$wid,$uid,$identity_id,$isExist]);
            if(!$isExist) return 0;
        }

        //2. 先作廢原本的
        $this->closeWorkPermitWorkerMen($type,$wid,$uid,$identity_id,$mod_user);

        //3. 新增
        $tmp = [];
        $tmp['wp_work_id']  = $wid;
        $tmp['user_id']     = $uid;
        $tmp['apply_type']  = 1;
        $tmp['engineering_identity_id'] = $identity_id;
        $tmp['isGuest'] = 'N';
        return $this->createWorkPermitWorker((object)$tmp,$mod_user);
    }

    /**
     * 取代原本的 指定專業人員<工地負責人，安衛人員>
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function closeWorkPermitWorkerMen($type = 1,$wid,$uid,$identity_id,$mod_user = 1)
    {
        $now     = date('Y-m-d H:i:s');

        //作廢原本的
        $UPD = wp_work_worker::where('wp_work_id',$wid);
        $UPD->where('isClose', 'N');
        if($type)
        {
            $UPD = $UPD->where('engineering_identity_id',$identity_id);
        } else {
            $UPD = $UPD->where('user_id',$uid);
        }
        return $UPD->update(['isClose'=>'Y','close_user'=>$mod_user,'close_stamp'=>$now]);
    }

    /**
     * 鎖定所有 的人員
     * @param $wid
     * @param $uid
     * @param $identity_id
     * @param int $mod_user
     * @return bool
     */
    public function freeWorkPermitWorkerIdentityMen($wid, $identity_id)
    {
        $ret = 0;

        $data = wp_work_worker::where('wp_work_id',$wid)->where('isClose','N');
        $data = $data->where('engineering_identity_id',$identity_id)->select('user_id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if(isset($val->user_id) && $val->user_id)
                {
                    if($this->freedWorkPermitWorkerMen($val->user_id))
                    {
                        $ret++;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態-審查通過
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkPermitWorkerMenOk($id,$mod_user = 1)
    {
        $tmp = [];
        $tmp['aproc']       = 'P';
        $tmp['mod_user']    = $mod_user;

        return wp_work_worker::where('wp_work_id',$id)->where('aproc','A')->where('isClose','N')->update($tmp);
    }

    /**
     * 釋放工作人員
     */
    public function freedWorkPermitWorkerMen($uid)
    {
        if(!$uid) return false;
        $tmp = [];
        $tmp['wp_work_id']  = 0;
        return rept_doorinout_t::where('b_cust_id',$uid)->where('door_date',date('Y-m-d'))->update($tmp);
    }

    /**
     * 修改 工作許可證-執行單-人員狀態-啟動
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkerMenReady($work_id, $b_cust_id ,$b_factory_id,$b_factory_d_id, $mod_user = 1)
    {
        $ret        = false;
        $today      = date('Y-m-d');
        $now        = date('Y-m-d H:i:s');
        $identity   = wp_work_worker::getIdentity($work_id, $b_cust_id);
        $door_id    = ($identity != 1)? $b_factory_d_id : 0;
        // 先嘗試尋找是否有已綁定工單的在廠人員資料
        $rept_id    = rept_doorinout_t::isExist($b_factory_id,$door_id,$today,[$b_cust_id],-1,[],$work_id);
        if (!$rept_id) { // 若找不到已綁定工單的在廠人員資料，則尋找已刷進未綁工單的人員在廠人員資料，並會更新為執行此工單
            $rept_id    = rept_doorinout_t::isExist($b_factory_id, $door_id, $today, [$b_cust_id], -1);
        } else {
            // 未找到在廠人員資料 或 在廠人員已綁定其他工單則不更新
        }
        list($door_type,$door_stamp) = rept_doorinout_t::getInOutTime($rept_id);
        //        dd($b_factory_id,$identity,$door_id,$rept_id);
        if(!$rept_id || !$b_factory_id) return $ret;
        $tmp = [];
        $tmp['aproc']       = 'R';
        $tmp['isIn']        = 'Y';
        $tmp['isLock']      = 'Y';
        $tmp['in_time']     = 1;
        $tmp['door_stime']  = $now; //啟動時間
        $tmp['door_stime1'] = ($door_type == 1 && $door_stamp)? $door_stamp : $now; //真實進場時間
        $tmp['mod_user']    = $mod_user; //異動人員

        $ret = wp_work_worker::where('wp_work_id',$work_id)->where('user_id',$b_cust_id)->where('aproc','P')->where('isClose','N')->update($tmp);

        if($ret)
        {
            //同時鎖住 每日進出廠紀錄表 ＋ 工作許可證ＩＤ
            $door = rept_doorinout_t::find($rept_id);
            $door->wp_work_id = $work_id;
            $ret = $door->save();
        }
        return $ret;
    }
    /**
     * 修改 工作許可證-執行單-人員狀態-啟動
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkPermitWorkerMenReady($work_id, $mod_user = 1)
    {
        $ret   = 0;
        $inAry = $lostAry = $rootAry = $workerAry = $allworkerAry = [];
        $today = date('Y-m-d');
        $store = wp_work::getStore($work_id);
        $identity_A = sys_param::getParam('PERMIT_SUPPLY_ROOT',1);
        $identity_B = sys_param::getParam('PERMIT_SUPPLY_SAFER',2);
        $work_aproc = wp_work::getAproc($work_id);
        //1. 找到已有進場紀錄之人員
        $data  = wp_work_worker::where('wp_work_id',$work_id)->where('aproc','P')->where('isClose','N');
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $b_cust_id               = $val->user_id;
                $allworkerAry[$val->id]  = $b_cust_id;
                $engineering_identity_id = $val->engineering_identity_id;
                //工地負責人
                if($identity_A == $engineering_identity_id)
                {
                    $rootAry[] = $val->id;
                }
                //施工人員
                if(!in_array($engineering_identity_id,[$identity_A,$identity_B]))
                {
                    $workerAry[$b_cust_id] = $b_cust_id;
                }

                if($rept_id = rept_doorinout_t::isExist($store,0,$today,[$b_cust_id],1))
                {
                    $inAry[$rept_id][] = $val->id;
                } else {
                    $lostAry[] = $val->id;
                }
            }
        }
        //dd([$store,$data,$inAry]);
        if(count($inAry))
        {
            //2.已經進場的人 加上註記
            foreach ($inAry as $rept_id => $val)
            {
                foreach ($val as $worker_id)
                {
                    //$user_id  = isset($allworkerAry[$worker_id])? $allworkerAry[$worker_id] : 0;
                    list($door_type,$door_stamp) = rept_doorinout_t::getInOutTime($rept_id);
                    $now = date('Y-m-d H:i:s');
                    $tmp = [];
                    $tmp['aproc']       = 'R';
                    $tmp['isIn']        = 'Y';
                    $tmp['isLock']      = 'Y';
                    $tmp['in_time']     = 1;
                    $tmp['door_stime']  = $now; //啟動時間
                    $tmp['door_stime1'] = ($door_type == 1 && $door_stamp)? $door_stamp : $now; //真實進場時間

                    if($this->setWorkPermitWorker($worker_id,$tmp,$mod_user))
                    {
                        $ret++;
                        $isLock = 1;
                        //2019-10-07 工地負責人
                        //進場人數超過2人 ＆ 不是Ａ級單 & 也沒有出現在 工作人員名單
                        //if(in_array($worker_id,$rootAry) && count($inAry) > 2 && $work_aproc != 'A' && !in_array($user_id,$workerAry))
//                    {
//                        $isLock = 0;
//                    }

                        if($isLock)
                        {
                            //同時鎖住 每日進出廠紀錄表 ＋ 工作許可證ＩＤ
                            $door = rept_doorinout_t::find($rept_id);
                            $door->wp_work_id = $work_id;
                            $door->save();
                        }
                    }
                }

            }
            //3.剩下沒有來的人 全部設定不得再進場
            foreach ($lostAry as $worker_id)
            {
                $tmp = [];
                $tmp['aproc']       = 'L'; //不得再次進場

                if($this->setWorkPermitWorker($worker_id,$tmp,$mod_user))
                {
                    //鎖定沒有進場的人員
                }
            }
        }

        return $ret;
    }

    /**
     * 補人作業
     */
    public function addWorkPermitWorker($work_id,$data = [],$mod_user = 1)
    {
        $ret = false;
        if(!$work_id || !count($data)) return $ret;
//        dd($data);
        foreach ($data as $b_cust_id => $identity_id)
        {
            if($worker_id = wp_work_worker::isApplyExist($work_id,$b_cust_id,$identity_id))
            {
                $INS = [];
                $INS['aproc']                   = 'P';

                $ret = $this->setWorkPermitWorker($worker_id,$INS,$mod_user);
            } else {
                $INS = [];
                $INS['wp_work_id']              = $work_id;
                $INS['user_id']                 = $b_cust_id;
                $INS['engineering_identity_id'] = $identity_id;
                $INS['apply_type']              = 2;
                $INS['isGuest']                 = 'N';
                $INS['aproc']                   = 'P';

                $ret = $this->createWorkPermitWorker($INS,$mod_user);
            }
            //如果有更新成功，則更新
            if($ret)
            {

            }
        }
        $this->pushToSupplyPermitWorkAddMen($work_id,count($data));
        return $ret;
    }

    /**
     * 補人作業
     */
    public function addWorkPermitWorker2($work_id,$data = [],$mod_user = 1)
    {
        $ret = false;
        if(!$work_id || !count($data)) return $ret;
        foreach ($data as $b_cust_id => $identity_id)
        {
//            dd($work_id,$b_cust_id,$identity_id);
            if($worker_id = wp_work_worker::isApplyExist($work_id,$b_cust_id,$identity_id,['A','P','R','O']))
            {
                $ret = false;
            } else {
                $INS = [];
                $INS['wp_work_id']              = $work_id;
                $INS['user_id']                 = $b_cust_id;
                $INS['engineering_identity_id'] = $identity_id;
                $INS['apply_type']              = 2;
                $INS['isGuest']                 = 'N';
                $INS['aproc']                   = 'A';

                $ret = $this->createWorkPermitWorker($INS,$mod_user);
            }
        }
        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態=離場
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkPermitWorkerMenOut($work_id,$isOut,$userAry = [],$mod_user = 1)
    {
        $today      = date('Y-m-d');
        $yesterday  = SHCSLib::addDay(-1);
        $ret        = 0;
        //1. 釋放 人員進出記錄表
        $tmp = [];
        $tmp['wp_work_id']  = 0;
        $ret = rept_doorinout_t::where('wp_work_id',$work_id)->whereIn('door_date',[$today,$yesterday]);
        if(count($userAry))
        {
            $ret = $ret->whereIn('b_cust_id',$userAry);
        }
        $ret = $ret->update($tmp);

        if($isOut)
        {
            //2. 釋放 工作許可證之人員
            $tmp = [];
            $tmp['aproc']       = 'O';
            $tmp['isOut']       = 'Y';
            $tmp['mod_user']    = $mod_user;

            $ret = wp_work_worker::where('wp_work_id',$work_id)->where('isIn','Y')->where('isClose','N');
            if(count($userAry))
            {
                $ret = $ret->whereIn('user_id',$userAry);
            }
            $ret = $ret->update($tmp);
        }
        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態=離場
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function chgWorkPermitWorkerMenInOut($work_id,$work_id2,$userAry = [],$mod_user = 1)
    {
        $today      = date('Y-m-d');
        $yesterday  = SHCSLib::addDay(-1);
        //轉換 人員進出記錄表
        $tmp = [];
        $tmp['wp_work_id']  = $work_id2;
        $ret = rept_doorinout_t::whereIn('wp_work_id',[0,$work_id])->whereIn('door_date',[$today,$yesterday]);
        if(count($userAry))
        {
            $ret = $ret->whereIn('b_cust_id',$userAry);
        }
        $ret = $ret->update($tmp);

        //2. 釋放 工作許可證之人員
        $tmp = [];
        $tmp['aproc']       = 'O';
        $tmp['isOut']       = 'Y';
        $tmp['mod_user']    = $mod_user;

        $ret = wp_work_worker::where('wp_work_id',$work_id)->where('isIn','Y')->where('isClose','N');
        if(count($userAry))
        {
            $ret = $ret->whereIn('user_id',$userAry);
        }
        $ret = $ret->update($tmp);

        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態=離場
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function updateWorkPermitWorkerMeIn($work_id)
    {
        $today      = date('Y-m-d');
        $userAry    = wp_work_worker::getUserAry($work_id);
        $ret        = 0;
        //1. 釋放 人員進出記錄表
        if(count($userAry))
        {
            $tmp = [];
            $tmp['wp_work_id']  = $work_id;
            $ret = rept_doorinout_t::whereIn('b_cust_id',$userAry)->where('wp_work_id',0)->where('door_date',$today)->update($tmp);
        }

        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態=離場
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWrokPermitWorkerMenOut($work_id,$user)
    {
        $ret = 0;
        if(!$work_id || !$user) return $ret;

        $workerAry = wp_work_worker::getAry($work_id,$user);
        //dd($worker_id);
        if(count($workerAry))
        {
            foreach ($workerAry as $worker_id)
            {
                $now = date('Y-m-d H:i:s');
                $tmp = [];
                $tmp['door_etime']   = $now;
                $tmp['door_etime1']  = $now;

                $ret = $this->setWorkPermitWorker($worker_id,$tmp,$user);
            }

        }

        return $ret;
    }

    /**
     * 修改 工作許可證-執行單-人員狀態=離場
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorkerMenWorkTime($work_id,$isStart = 1,$mod_user)
    {
        $now = date('Y-m-d H:i:s');
        $tmp = [];
        if($isStart)
        {
            $tmp['work_stime']  = $now;
        } else {
            $tmp['work_etime']  = $now;
        }
        $tmp['mod_user']    = $mod_user;

        return wp_work_worker::where('wp_work_id',$work_id)->where('isIn','Y')->where('isClose','N')->update($tmp);
    }


    /**
     * 修改 工作許可證_工作人員
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setWorkPermitWorker($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = wp_work_worker::find($id);
        if(!isset($UPD->wp_work_id)) return $ret;
        //入廠時間
        if(isset($data->door_stime1) && $data->door_stime1 && $data->door_stime1 !== $UPD->door_stime1)
        {
            $isUp++;
            $UPD->door_stime1 = $data->door_stime1;
        }
        //離廠時間
        if(isset($data->door_etime1) && $data->door_etime1 && $data->door_etime1 !== $UPD->door_etime1)
        {
            $isUp++;
            $UPD->door_etime1 = $data->door_etime1;
        }
        //啟動時間
        if(isset($data->door_stime) && $data->door_stime && $data->door_stime !== $UPD->door_stime)
        {
            $isUp++;
            $UPD->in_time    += 1;
            $UPD->door_stime = $data->door_stime;
        }
        //離場場時間
        if(isset($data->door_etime) && $data->door_etime && $data->door_etime !== $UPD->door_etime)
        {
            $isUp++;
            if(!is_null($UPD->door_stime) && $UPD->aproc == 'O')
            {
                $add_time           = (strtotime($data->door_etime) - strtotime($UPD->door_stime));
                if($add_time > 0) $UPD->door_total_time   += $add_time;
            }
            $UPD->out_time          += 1;
            $UPD->door_etime        = $data->door_etime;
        }
        //施工開始時間
        if(isset($data->work_stime) && $data->work_stime && $data->work_stime !== $UPD->work_stime)
        {
            $isUp++;
            $UPD->work_stime = $data->work_stime;
        }
        //施工結束時間
        if(isset($data->work_etime) && $data->work_etime && $data->work_etime !== $UPD->work_etime)
        {
            $isUp++;
            $add_time               = (strtotime($data->work_etime) - strtotime($UPD->work_stime));
            if($add_time > 0) $UPD->work_total_time   += $add_time;
            $UPD->work_etime        = $data->work_etime;
        }
        //在廠
        if(isset($data->isIn) && in_array($data->isIn,['N','Y']) && $data->isIn !== $UPD->isIn)
        {
            $isUp++;
            $UPD->isIn         = $data->isIn;
        }
        //離場
        if(isset($data->isOut) && in_array($data->isOut,['N','Y']) && $data->isOut !== $UPD->isOut)
        {
            $isUp++;
            $UPD->isOut         = $data->isOut;
        }
        //啟動 鎖定
        if(isset($data->isLock) && in_array($data->isLock,['N','Y']) && $data->isLock !== $UPD->isLock)
        {
            $isUp++;
            $UPD->isLock         = $data->isLock;
        }
        //進度 審查
        if(isset($data->aproc) && in_array($data->aproc,['A','P','R','O','L','C']) && $data->aproc !== $UPD->aproc)
        {
            $isUp++;
            $UPD->aproc         = $data->aproc;
            $UPD->charge_user   = $mod_user;
            $UPD->charge_stamp  = $now;

            if($data->aproc == 'P')
            {
                //判斷是否在廠
                $store_id = wp_work::getStore($UPD->wp_work_id);
                $today = date('Y-m-d');
                $doorObj = rept_doorinout_t::getData($store_id,$UPD->user_id,$today,1);
                if(isset($doorObj->wp_work_id) && $doorObj->wp_work_id == 0)
                {
                    $UPD->isIn          = 'Y';
                    $UPD->isLock        = 'Y';
                    $UPD->door_stime    = SHCSLib::getNow();
                    $UPD->door_stime1   = $doorObj->door_stamp;
                    $UPD->save();
                    //如果現在沒有掛工單，且在廠
                    $tmp = [];
                    $tmp['wp_work_id']  = $UPD->wp_work_id;
                    rept_doorinout_t::where('id',$doorObj->id)->update($tmp);
                }
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
     * 取得 工作許可證_工作人員
     *
     * @return array
     */
    public function getApiWorkPermitWorkerList($work_id,$extAry = [1,2])
    {
        $ret = array();
        $workerTypeAry  = SHCSLib::getCode('PERMIT_WORKER_TYPE');
        $workerAprocAry = SHCSLib::getCode('PERMIT_WORKER_APROC');
        //取第一層
        $data = wp_work_worker::where('wp_work_id',$work_id)->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('engineering_identity_id',$extAry);
        }
        $data = $data->orderby('engineering_identity_id')->orderby('aproc')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['name']           = User::getName($v->user_id);
                $data[$k]['apply_type_name']= isset($workerTypeAry[$v->apply_type])? $workerTypeAry[$v->apply_type] : '';
                $data[$k]['aproc_name']     = isset($workerAprocAry[$v->aproc])? $workerAprocAry[$v->aproc] : '';
                $data[$k]['door_stime']     = substr($v->door_stime,0,19);
                $data[$k]['door_etime']     = substr($v->door_etime,0,19);
                $data[$k]['work_stime']     = substr($v->work_stime,0,19);
                $data[$k]['work_etime']     = substr($v->work_etime,0,19);
                $data[$k]['apply_user']     = User::getName($v->apply_user);
                $data[$k]['charge_user']    = User::getName($v->charge_user);
                $data[$k]['close_user']     = User::getName($v->close_user);
                $data[$k]['new_user']       = User::getName($v->new_user);
                $data[$k]['mod_user']       = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_工作人員 For APP
     *
     * @return array
     */
    public function getApiWorkPermitWorker($id,$iid = 0,$extAry = [],$checkDoorInout = 0,$isActive = 0)
    {
        $ret = array();
        $store  = wp_work::getStore($id);
        $iidAry = b_supply_engineering_identity::getSelect(0);
        //取第一層
        $data = wp_work_worker::
        join('b_supply_engineering_identity as e','e.id','=','wp_work_worker.engineering_identity_id')->
        where('wp_work_worker.wp_work_id',$id)->where('wp_work_worker.isClose','N')->
        select('wp_work_worker.*','e.name as identity');
        //指定工程身分
        if($iid)
        {
            $data = $data->where('wp_work_worker.engineering_identity_id',$iid);
        }
        //去除特定工程身分
        if(count($extAry))
        {
            $data = $data->whereNotIn('wp_work_worker.engineering_identity_id',$extAry);
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $k => $v)
            {
                $isOk = 1;
                //如果限定 有效可施工的成員
                if($checkDoorInout && $isActive && !in_array($v->aproc,['R','P','O']))
                {
                    $isOk = 0;
                }
                if(!$isOk) continue;

                if(isset($ret[$v->user_id]))
                {
                    if ($ret[$v->user_id]['apply_type'] == $v->apply_type) { // 一般申請
                        $ret[$v->user_id]['identity']  .= '，' . $v->identity;
                    } else if ($v->apply_type == 2) { // 補人 (通常補人只能補一次並只有一種身分，若是補人則會蓋過原始申請之身分)
                        $ret[$v->user_id]['apply_type'] = $v->apply_type;
                        $ret[$v->user_id]['identity']  = $v->identity;
                        $ret[$v->user_id]['isIn']  = $v->isIn; // 如果補人則以補人的進場狀態為主 (非即時入廠狀態，而是只要有曾經有綁定到此工單即可)
                    }
                } else {
                    $isIn        = $v->isIn;
                    $door_memo   = '';
                    if($checkDoorInout)
                    {
                        list($isIn,$door_memo)          = HTTCLib::getMenDoorStatus($store,$v->user_id);
                        $isIn = $isIn ? 'Y' : 'N'; // 此為即時入場狀態，但不一定是綁定到此施工人員的工單
                    }
                    $tmp = [];
                    $tmp['id']          = ($v->id);
                    $tmp['user_id']     = ($v->user_id);
                    $tmp['identity_id'] = ($v->engineering_identity_id);
                    $tmp['identity']    = $v->identity;
                    $tmp['name']        = User::getName($v->user_id);
                    $tmp['apply_type']  = ($v->apply_type);
                    $tmp['isIn']        = ($v->isIn);
                    $tmp['isOut']       = ($v->isOut);
                    $tmp['isGuest']     = ($v->isGuest);
                    $tmp['door_stime']  = !is_null($v->door_stime)? $v->door_stime : '';
                    $tmp['door_etime']  = !is_null($v->door_etime)? $v->door_etime : '';
                    $tmp['work_stime']  = !is_null($v->work_stime)? $v->work_stime : '';
                    $tmp['work_etime']  = !is_null($v->work_etime)? $v->work_etime : '';
                    $tmp['door_result'] = $isIn;
                    $tmp['door_memo']   = $door_memo;
                    $ret[$v->user_id]   = $tmp;
                }
            }
            sort($ret);
        }

        return $ret;
    }

    /**
     * 取得 工作許可證_工作人員證書字號 For APP
     *
     * @return array
     */
    public function getApiWorkPermitWorkerLicenseCodes($workId, $licenseId)
    {
        $ret = [];
        $Worker_Ary = $this->getApiWorkPermitWorker($workId, 0, [1, 2], 1, 1);
        $WorkerData = array();
        foreach ($Worker_Ary as $key => $value) {
            $license_code = b_supply_member_l::getLicense($value['user_id'], $licenseId);
            $WorkerData[$value['identity_id']][$key] = $license_code;
        }
        foreach ($WorkerData as $key2 => $value2) {
            $license_codes = '';
            foreach ($value2 as $value3) {
                if (empty($value3)) {
                    continue;
                }
                if (!empty($license_codes)) {
                    $license_codes .= ',';
                }
                $license_codes .= $value3;
            }
            $ret[$key2] = $license_codes;
        }
        return $ret;
    }

    /**
     * 取得 工作許可證_工作人員姓名 For APP
     *
     * @return array
     */
    public function getApiWorkPermitWorkerNames($workId)
    {
        $ret = [];
        $Worker_Ary = $this->getApiWorkPermitWorker($workId, 0, [1, 2], 1, 1);
        $WorkerData = array();
        foreach ($Worker_Ary as $key => $value) {
            $user_name = USER::getName($value['user_id']);
            $WorkerData[$value['identity_id']][$key] = $user_name;
        }
        foreach ($WorkerData as $key2 => $value2) {
            $user_names = '';
            foreach ($value2 as $value3) {
                if (empty($value3)) {
                    continue;
                }
                if (!empty($user_names)) {
                    $user_names .= ',';
                }
                $user_names .= $value3;
            }
            $ret[$key2] = $user_names;
        }
        return $ret;
    }

    /**
     * 取得 工作許可證 對應工程身份之 <已在廠>承攬商成員
     *
     * @return array
     */
    public function getApiWorkPermitWorkerForPrint($wid,$taid ,$identity_id = 0)
    {
        $ret = '';
        if(!$wid || !$taid) return $ret;
        $extAry = [];
        //取第一層
        $identity_id = $identity_id ? $identity_id : (Int)wp_permit_topic_a::getIdentity($taid);
        if($identity_id)
        {
            if($identity_id == 10){
                $extAry = [1,2,3,4,5,6,8,9];
            }
            if(in_array($identity_id,[9,10])) $identity_id = 0;

            $data = wp_work_worker::getLockMenSelect($wid,$identity_id,0,1,0,$extAry);

//            if(!$identity_id) dd([$wid,$taid,$identity_id,$data]);

            if(count($data))
            {
                $str = '';

                foreach ($data as $val)
                {
                    $iid = isset($val['iid'])? $val['iid'] : '';
                    if($str) $str .= '，';
                    $str .= isset($val['name'])? $val['name'] : '';
                    $str .= (in_array($iid,[1,2]) && isset($val['iid_name']))? '('.$val['iid_name'].')'  : '';
                }
                $ret = $str;
            }
        }

        return $ret;
    }

}
