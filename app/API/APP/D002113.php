<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTargetTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkItemDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Lib\HtmlLib;
use App\Lib\HTTCLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_l;
use App\Model\Supply\b_supply_engineering_identity;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\Report\rept_doorinout_t;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002113 [工作許可證]-補人作業.
 * 目的：取得　當日工作許可證
 *
 */
class D002113 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait;
    use WorkPermitWorkOrderlineTrait,WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderCheckTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,WorkPermitProcessTargetTrait;
    use WorkPermitProcessTopicTrait,WorkPermitWorkProcessTopicOption,WorkPermitTopicOptionTrait;
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
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id      = (isset($jsonObj->id))?                $jsonObj->id : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $this->b_factory_id = isset($wp_work->b_factory_id)? $wp_work->b_factory_id : 0;
            $this->e_project_id = isset($wp_work->e_project_id)? $wp_work->e_project_id : 0;
            $this->aproc        = isset($wp_work->aproc)? $wp_work->aproc : '';
            $this->sdate        = isset($wp_work->sdate)? $wp_work->sdate : '';
            $charge_memo        = isset($wp_work->charge_memo)? $wp_work->charge_memo : '';
            $this->dept2        = isset($wp_work->be_dept_id2)? $wp_work->be_dept_id2 : 0;
            $this->b_supply_id  = isset($wp_work->b_supply_id)? $wp_work->b_supply_id : 0;
            $work_close         = isset($wp_work->isClose)? $wp_work->isClose : 'Y';

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
            //2.5.1　施工中　不可補人
//            if($isSuc == 'Y' && $this->aproc == 'R')
//            {
//                $isSuc          = 'N';
//                $this->errCode  = 'E00200284';// 目前施工中，不可補人
//            }
            //2.5.2 收工中
            if($isSuc == 'Y' && $this->aproc == 'O')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200285';// 目前申請收工中，不可補人
            }
            //2.5.3 已經收完
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
            if($isSuc == 'Y' && $work_close === 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200217';// 該工作許可證已作廢
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;
                //所處部門
                $dept   = ($this->bc_type == 2)? view_dept_member::getDept($this->b_cust_id) : 0;
                if($dept)
                {
                    if($this->dept2 != $dept)
                    {
                        $isSuc              = 'N';
                        $this->errCode      = 'E00200261';// 負責監造不屬於該部門
                        $this->errParam1    = be_dept::getName($this->dept2);// 該部門
                    }
                } else {
                    $b_supply_id     = b_supply_member::getSupplyId($this->b_cust_id);
                    if($b_supply_id != $this->b_supply_id)
                    {
                        $isSuc              = 'N';
                        $this->errCode      = 'E00200283';// 此工單非敝公司所有
                    }
                }
                if($isSuc == 'Y')
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
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
            $isSupply   = 0;
            $deptid     = 0;
            //承攬商
            if($this->bc_type == 3) {
                $isSupply       = 1;
            }
            //
            if(1)
            {
                $identityMemberAry      = [];
                $engineeringIdentityAry = b_supply_engineering_identity::getSelect(0);
                $idAry  = $this->getApiWorkPermitWorkerList($this->work_id,[]);
                if(count($idAry))
                {
                    foreach ($idAry as $key => $val)
                    {
                        if($val->user_id > 0)
                        {
                            $work_memo   = '';
                            $tmp = [];
                            $tmp['name']            = $val->name;
                            $tmp['aproc_name']      = $val->aproc_name;
                            $tmp['apply_type']      = $val->apply_type;
                            $tmp['apply_type_name'] = $val->apply_type_name;
                            $tmp['identity']   = isset($engineeringIdentityAry[$val->engineering_identity_id])? $engineeringIdentityAry[$val->engineering_identity_id] : '';
                            if($this->aproc == 'W')
                            {
                                //是否已經在廠
                                list($isIn,$work_memo) = HTTCLib::getMenDoorStatus($this->b_factory_id,$val->user_id);
                                if(!$isIn) $work_memo = $work_memo;
                            } else {
                                //顯示進場紀錄
                                $door_stime = !is_null($val->door_stime)? $val->door_stime : '';
                                $door_etime = !is_null($val->door_etime)? $val->door_etime : '';
                                $work_time  = !is_null($val->work_stime)? $val->work_stime : '';
                                if($work_time) $work_time .= !is_null($val->work_etime)? '~'.$val->work_etime : '';
                                if($door_stime) $work_memo = Lang::get('sys_base.base_40243',['time1'=>$door_stime,'time2'=>$door_etime]);
                                if($work_time) $work_memo.= Lang::get('sys_base.base_40249',['time3'=>$work_time]);
                            }
                            $tmp['work_time'] = $work_memo;

                            $identityMemberAry[] = $tmp;
                        }
                    }
                }


                //加人作業
                $addmemberAry   = [];
                $identityAry    = e_project_l::getSelect($this->e_project_id,1,0,[0]);
                $worker_aproc   = (in_array($this->aproc,['A','W']))? '' : 'R';
                $isIn           = ($worker_aproc == 'R')? 1 : 0;
                //工安＆工負
                $rootAry        = wp_work_worker::getRootMen($this->work_id);
                $isInMenAry     = wp_work_worker::getSelect($this->work_id,0,0,0,['A','P',$worker_aproc],'L',$isIn);
                //排除當前有執行工單的人員
                $hasWorkMenAry  = rept_doorinout_t::where('wp_work_id', '!=', 0)
                ->where('door_date', date('Y-m-d'))
                ->select('b_cust_id')->pluck('b_cust_id')->toArray();
                $isInMenAry = array_unique(array_merge($isInMenAry, $hasWorkMenAry));
                //排除當前有執行工單的人員
                $hasWorkMenAry  = rept_doorinout_t::where('wp_work_id', '!=', 0)
                ->where('door_date', date('Y-m-d'))
                ->select('b_cust_id')->pluck('b_cust_id')->toArray();
                $isInMenAry = array_unique(array_merge($isInMenAry, $hasWorkMenAry));

                foreach ($identityAry as $iid => $iname)
                {
                    $tmp = [];
                    $tmp['id']      = $iid;
                    $tmp['name']    = $iname;
                    if($iid == 9)
                    {
                        $menAry = array_merge($isInMenAry,$rootAry);
                    } else {
                        $menAry = $isInMenAry;
                    }
                    $tmp['men']    = view_door_supply_whitelist_pass::getProjectMemberWhitelistSelect($this->e_project_id,[],$iid,1,1,$menAry,$this->sdate);
                    if(count($tmp['men']) > 1)
                    {
                        $addmemberAry[] = $tmp;
                    }
                }

                $ret['worker']      = $identityMemberAry;
                $ret['addmember']   = $addmemberAry;
            }

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
