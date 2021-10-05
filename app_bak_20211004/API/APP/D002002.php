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
use App\Model\Engineering\e_project_s;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\User;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_permit;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002002 工作許可證-申請.
 * 目的：申請 當日工作許可證<僅限 承攬商申請>
 *
 */
class D002002 extends JsonApi
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
        $apply_max_day      = sys_param::getParam('PERMIT_APPLY_MAX_DAY',3);
        $apply_max_time     = sys_param::getParam('PERMIT_APPLY_MAX_TIME','12:00');
        $apply_is_time      = sys_param::getParam('PERMIT_APPLY_MAX_TIME_ACTIVE',0);
        $apply_max_date     = SHCSLib::addDay($apply_max_day);
        $today              = date('Y-m-d');
        $nowtime            = date(' H:i:s');
        $apply_limit_time   = date('Y-m-d').' '.$apply_max_time.':00';

        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token                          = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->project_id               = (isset($jsonObj->project_id))? $jsonObj->project_id : 0;
            $this->wp_permit_id             = 1;
            $this->b_factory_id             = (isset($jsonObj->store_id))? $jsonObj->store_id : '';
            $this->b_factory_a_id           = (isset($jsonObj->store_a_id))? $jsonObj->store_a_id : '';
            $this->be_dept_id1              = (isset($jsonObj->dept_id))? $jsonObj->dept_id : '';
            $this->b_factory_memo           = (isset($jsonObj->store_memo))? $jsonObj->store_memo : '';
            $this->wp_permit_workitem_memo  = (isset($jsonObj->workitem_memo))? $jsonObj->workitem_memo : '';
            $this->supply_worker            = (isset($jsonObj->supply_worker))? $jsonObj->supply_worker : 0;
            $this->supply_safer             = (isset($jsonObj->supply_safer))? $jsonObj->supply_safer : 0;
            $this->other_worker             = (isset($jsonObj->other_worker))? $jsonObj->other_worker : [];
            $this->sdate                    = (isset($jsonObj->sdate))? $jsonObj->sdate : '';
            $isExistToken                   = TokenLib::isTokenExist(0, $token,$this->tokenType);
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
            //2.3 請選擇施工廠區＆場地
            if($isSuc == 'Y' && (!$this->b_factory_id || !$this->b_factory_a_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200202';// 請選擇施工廠區＆場地
            }
            //2.4 請選擇工作許可證＆危險等級
            if($isSuc == 'Y' && (!$this->wp_permit_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200203';// 請選擇工作許可證＆危險等級
            }
            //2.6 施工日期不可小於今日
            if($isSuc == 'Y' && (!$this->sdate || !CheckLib::isDate($this->sdate) || (strtotime($this->sdate) < strtotime(date('Y-m-d')))) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200206';// 施工日期必須大於今日
            }
            //2.7-a 工廠區＆場地 不正常
            /*if($isSuc == 'Y' && !be_dept::isExist($this->be_dept_id1,$this->b_factory_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200208';// 施工場地不在指定該廠區內
            }*/
            //2.7-b 工廠區＆場地 不正常
            if($isSuc == 'Y' && !b_factory_a::isExist($this->b_factory_id,$this->b_factory_a_id) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200227';// 施工場地不在指定該廠區內
            }
            //2.8 施工日期不可大於五天[系統參數]
            if($isSuc == 'Y' && (strtotime($this->sdate) > strtotime($apply_max_date)) )
            {
                $isSuc              = 'N';
                $this->errCode      = 'E00200209';// 施工日期不可大於五天
                $this->errParam1    = $apply_max_day;// 超過幾天
            }
            //2.9 已超過當日可申請工作許可證時間[系統參數]
            if($isSuc == 'Y' && $apply_is_time && ($this->sdate == $today && strtotime($this->sdate.$nowtime) > strtotime($apply_limit_time)) )
            {
                $isSuc              = 'N';
                $this->errCode      = 'E00200210';// 已超過當日可申請工作許可證時間
                $this->errParam1    = $apply_max_time;// 超過幾天
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $other_worker    = [];

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
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
                //2019-06-19 轉換昱俊給的物件
                if(count($this->other_worker))
                {
                    foreach ($this->other_worker as $val)
                    {
                        $tmp = SHCSLib::toArray($val);
                        $other_worker += $tmp;
                    }
                }
                //20.1
                if($this->supply_worker == $this->supply_safer)
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200213';// 不可以同一個人
                }
                //判斷是否有無施工人員
//                if(!count($other_worker))
//                {
//                    $isSuc          = 'N';
//                    $this->errCode  = 'E00200220';// 沒有施工人員
//                } else{
                    foreach ( $other_worker as $uid => $iid)
                    {
                        if(!view_user::isExist($uid))
                        {
                            $isSuc          = 'N';
                            $this->errCode  = 'E00200221';// 該施工人員不存在
                            break;
                        }
                    }
//                }
//                dd($other_worker);

                if($isSuc == 'Y')
                {
                    $upAry = [];
                    $upAry['e_project_id']              = $this->project_id;
                    $upAry['b_supply_id']               = $b_supply_id;
                    $upAry['supply_worker']             = $this->supply_worker;
                    $upAry['supply_safer']              = $this->supply_safer;
                    $upAry['identityMember']            = $other_worker;
                    $upAry['wp_permit_id']              = $this->wp_permit_id;
                    $upAry['b_factory_memo']            = $this->b_factory_memo;
                    $upAry['b_factory_id']              = is_numeric($this->b_factory_id) ? $this->b_factory_id : 0;
                    $upAry['b_factory_a_id']            = is_numeric($this->b_factory_a_id) ? $this->b_factory_a_id : 0;
                    $upAry['be_dept_id1']               = is_numeric($this->be_dept_id1) ? $this->be_dept_id1 : 0;
                    $upAry['wp_permit_workitem_memo']   = isset($this->wp_permit_workitem_memo) ? $this->wp_permit_workitem_memo : '';
                    $upAry['sdate']                     = CheckLib::isDate($this->sdate) ? $this->sdate : '';
                    $upAry['edate']                     = CheckLib::isDate($this->sdate) ? $this->sdate : '';
                    //dd($upAry);
                    if($ret = $this->createWorkPermitWorkOrder($upAry,$this->b_cust_id))
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
        return json_encode($ret);
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

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
