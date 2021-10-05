<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\SHCSLib;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_license;
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use App\Model\WorkPermit\wp_work;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002101 [工作許可證]-申請.
 * 目的：申請 當日工作許可證<僅限 承攬商申請>
 *
 */
class D002101 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderlineTrait,PushTraits;
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
        $apply_max_day      = sys_param::getParam('PERMIT_APPLY_MAX_DAY',3); //最早能申請天數
        $apply_max_time     = sys_param::getParam('PERMIT_APPLY_MAX_TIME','12:00'); //申請時間限制
        $apply_is_time      = sys_param::getParam('PERMIT_APPLY_MAX_TIME_ACTIVE',0); //是否檢查申請時間限制
        $apply_max_date     = SHCSLib::addDay($apply_max_day);//最早申請日期
        $today              = date('Y-m-d');
        $nowtime            = date(' H:i:s');
        $apply_limit_time   = date('Y-m-d').' '.$apply_max_time.':00';
        $this->work_id      = 0;

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token                          = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->project_id               = (isset($jsonObj->project_id))? $jsonObj->project_id : 0;
            $this->wp_permit_id             = 1;
            $this->b_factory_a_id           = (isset($jsonObj->b_factory_a_id))? $jsonObj->b_factory_a_id : 0;
            $this->b_factory_b_id           = (isset($jsonObj->b_factory_b_id))? $jsonObj->b_factory_b_id : 0;
            $this->b_factory_d_id           = (isset($jsonObj->b_factory_d_id))? $jsonObj->b_factory_d_id : 0;
            $this->be_dept_id2              = (isset($jsonObj->dept_id))? $jsonObj->dept_id : 0;
            $this->project_charge           = (isset($jsonObj->charge_user))? $jsonObj->charge_user : 0;
            $this->b_factory_memo           = (isset($jsonObj->b_factory_memo))? $jsonObj->b_factory_memo : '';
            $this->b_car_memo               = (isset($jsonObj->b_car_memo))? $jsonObj->b_car_memo : '';
            $this->wp_permit_workitem_memo  = (isset($jsonObj->workitem_memo))? $jsonObj->workitem_memo : '';
            $this->sdate                    = (isset($jsonObj->sdate))? $jsonObj->sdate : '';
            $this->wp_permit_shift_id       = (isset($jsonObj->shift))? $jsonObj->shift : 1;

            $this->supply_worker            = (isset($jsonObj->supply_worker))? $jsonObj->supply_worker : [];
            $this->supply_safer             = (isset($jsonObj->supply_safer))? $jsonObj->supply_safer : [];
            $this->other_worker             = (isset($jsonObj->supply_identity))? $jsonObj->supply_identity : [];
            $isExistToken                   = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $chargeDept                     = view_dept_member::getDept($this->project_charge);
            //dd($jsonObj->other_worker);
            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //2.2 請選擇工程案件
            if($isSuc == 'Y' && !$this->project_id )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200201';// 請選擇工程案件
            }
            //2.3 請選擇施工場地＆施工地點
            if($isSuc == 'Y' && (!$this->b_factory_a_id || !$this->b_factory_b_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200202';// 請選擇施工場地＆施工地點
            }
            if($isSuc == 'Y' && (!$this->b_factory_d_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200263';// 請選擇門別
            }
            //2.4 工負
            if($isSuc == 'Y' && (!count($this->supply_worker)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200211';// 請選擇工負
            } else {
                foreach ($this->supply_worker as $val)
                {
                    $uid = isset($val->uid)? $val->uid : 0;
                    if(!$uid)
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200211';// 請選擇工負
                        continue;
                    }
                }
            }
            //2.5 工安
            if($isSuc == 'Y' && (!count($this->supply_safer)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200212';// 請選擇工安
            } else {
                foreach ($this->supply_safer as $val)
                {
                    $uid = isset($val->uid)? $val->uid : 0;
                    if(!$uid)
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200212';// 請選擇工安
                        continue;
                    }
                }
            }
            //2.6 工安
            if($isSuc == 'Y' && (!count($this->other_worker)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200220';// 沒有施工人員
            } else {
                foreach ($this->other_worker as $val)
                {
                    $uid = isset($val->uid)? $val->uid : 0;
                    $iid = isset($val->iid)? $val->iid : 0;
                    if(!$uid || !$iid)
                    {
                        $isSuc          = 'N';
                        $this->errCode  = 'E00200262';// :param1 缺少工程身分
                        $this->errParam1= User::getName($uid);
                        continue;
                    }
                    elseif(!e_project_license::isExist($this->project_id,$uid,$iid))
                    {
                        $isSuc              = 'N';
                        $this->errCode      = 'E00200221';// 該施工人員 :name 不存在
                        $this->errParam1    = User::getName($uid);
                        continue;
                    }
                }
            }
            //2.7 施工日期不可小於今日
            if($isSuc == 'Y' && (!$this->sdate || !CheckLib::isDate($this->sdate)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00300208';// 日期格式不正確
            }
            //2.7 施工日期不可小於今日
            if($isSuc == 'Y' && ((strtotime($this->sdate) < strtotime(date('Y-m-d')))) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200206';// 施工日期必須大於今日
            }
            //2.8 該施工地點不存在該場地 不正常
            if($isSuc == 'Y' && !b_factory_b::isLocalExist($this->b_factory_a_id,$this->b_factory_b_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200227';// 該施工地點不存在該場地
            }
            //2.9 施工日期不可大於X天[系統參數]
            if($isSuc == 'Y' && (strtotime($this->sdate) > strtotime($apply_max_date)) )
            {
                $isSuc              = 'N';
                $this->errCode      = 'E00200209';// 施工日期不可大於X天
                $this->errParam1    = $apply_max_day;// 超過幾天
                $this->errParam1    = $apply_max_day;// 超過幾天
            }
            //2.10 已超過當日可申請工作許可證X時間[系統參數]
            if($isSuc == 'Y' && $apply_is_time && ($this->sdate == $today && strtotime($this->sdate.$nowtime) > strtotime($apply_limit_time)) )
            {
                $isSuc              = 'N';
                $this->errCode      = 'E00200210';// 已超過當日可申請工作許可證時間
                $this->errParam1    = $apply_max_time;// 超過幾天
            }
            //2.11 未選擇負責監造
//            if($isSuc == 'Y' && !$this->project_charge )
//            {
//                $isSuc              = 'N';
//                $this->errCode      = 'E00200260';// 未選擇負責監造
//            }
            //2.12 負責監造不屬於該部門
            if($isSuc == 'Y' && $this->project_charge && $chargeDept != $this->be_dept_id2 )
            {
                $isSuc              = 'N';
                $this->errCode      = 'E00200261';// 負責監造不屬於該部門
                $this->errParam1    = be_dept::getName($this->be_dept_id2);// 該部門
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey    = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;
                $b_supply_id     = b_supply_member::getSupplyId($this->b_cust_id);

                //承攬商
                if(!$b_supply_id)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200104';// 非承攬商
                }
                if($isSuc == 'Y' && !e_project::isExist($this->project_id,$b_supply_id,'Y'))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200205';// 非有效之工程案件
                }

                if($isSuc == 'Y')
                {
                    $upAry = [];
                    $upAry['e_project_id']              = $this->project_id;
                    $upAry['b_supply_id']               = $b_supply_id;
                    $upAry['supply_worker']             = $this->supply_worker;
                    $upAry['supply_safer']              = $this->supply_safer;
                    $upAry['identityMember']            = $this->other_worker;;
                    $upAry['wp_permit_id']              = $this->wp_permit_id;
                    $upAry['b_factory_id']              = b_factory_a::getStoreId($this->b_factory_a_id);
                    $upAry['b_factory_a_id']            = $this->b_factory_a_id;
                    $upAry['b_factory_b_id']            = $this->b_factory_b_id;
                    $upAry['b_factory_d_id']            = $this->b_factory_d_id;
                    $upAry['be_dept_id2']               = $this->be_dept_id2;
                    $upAry['project_charge']            = $this->project_charge;
                    $upAry['b_factory_memo']            = $this->b_factory_memo;
                    $upAry['b_car_memo']                = isset($this->b_car_memo) ? $this->b_car_memo : '';
                    $upAry['wp_permit_workitem_memo']   = isset($this->wp_permit_workitem_memo) ? $this->wp_permit_workitem_memo : '';
                    $upAry['sdate']                     = $this->sdate;
                    $upAry['edate']                     = $this->sdate;
                    $upAry['wp_permit_shift_id']        = $this->wp_permit_shift_id;
                    //dd($upAry);
                    if($this->work_id = $this->createWorkPermitWorkOrder($upAry,$this->b_cust_id))
                    {
                        $this->reply     = 'Y';
                        $this->errCode   = '';

                    } else {
                        $this->reply     = 'N';
                        $this->errCode   = 'E00200207'; //申請失敗
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
            $ret['permit_no'] = wp_work::getNo($this->work_id);
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
