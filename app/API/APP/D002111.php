<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\SessTraits;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorklineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderDangerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkTopicTrait;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_permit_pipeline;
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
 * D002111
 * 目的： 工作許可證審查
 *
 */
class D002111 extends JsonApi
{
    use BcustTrait;
    use WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,SessTraits;
    use WorkPermitWorkOrderListTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitWorklineTrait,WorkPermitWorkOrderlineTrait;
    use WorkPermitWorkOrderProcessTrait ;
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
            $token                          = (isset($jsonObj->token))?             $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->work_id                  = (isset($jsonObj->id))?                $jsonObj->id : '';
            $this->b_factory_a_id           = (isset($jsonObj->b_factory_a_id))?    $jsonObj->b_factory_a_id : 0;
            $this->b_factory_b_id           = (isset($jsonObj->b_factory_b_id))?    $jsonObj->b_factory_b_id : 0;
            $this->b_factory_d_id           = (isset($jsonObj->b_factory_d_id))?    $jsonObj->b_factory_d_id : 0;
            $this->wp_permit_danger         = (isset($jsonObj->wp_permit_danger))?  $jsonObj->wp_permit_danger : '';
            $this->wp_permit_shift_id       = (isset($jsonObj->wp_permit_shift_id))?  $jsonObj->wp_permit_shift_id : 0;
            $this->sdate                    = (isset($jsonObj->sdate))?             $jsonObj->sdate : '';
            $this->be_dept_id1              = (isset($jsonObj->be_dept_id1))?       $jsonObj->be_dept_id1 : 0;
            $this->be_dept_id3              = (isset($jsonObj->be_dept_id3))?       $jsonObj->be_dept_id3 : 0;
            $this->be_dept_id4              = (isset($jsonObj->be_dept_id4))?       $jsonObj->be_dept_id4 : 0;
            $this->b_factory_memo           = (isset($jsonObj->b_factory_memo))?    $jsonObj->b_factory_memo : '';
            $this->wp_permit_workitem_memo  = (isset($jsonObj->wp_permit_workitem_memo))?    $jsonObj->wp_permit_workitem_memo : '';
            $this->isHoliday                = (isset($jsonObj->isHoliday) && $jsonObj->isHoliday == 'Y')? 'Y' : 'N';
            $this->isOvertime               = (isset($jsonObj->isOvertime) && $jsonObj->isOvertime == 'Y')? 'Y' : 'N';
            $this->agree                    = (isset($jsonObj->agree) && $jsonObj->agree == 'Y')? 'Y' : 'N';
            $this->charge_memo              = (isset($jsonObj->charge_memo))?       $jsonObj->charge_memo : '';
            $this->itemwork                 = (isset($jsonObj->wp_item) && count($jsonObj->wp_item))?  $jsonObj->wp_item : [];
            $this->line                     = (isset($jsonObj->wp_line) && count($jsonObj->wp_line))? $jsonObj->wp_line : [];
            $this->wp_check                 = (isset($jsonObj->wp_check) && count($jsonObj->wp_check))? $jsonObj->wp_check : [];
            $this->wp_danger                = (isset($jsonObj->wp_danger) && count($jsonObj->wp_danger))? $jsonObj->wp_danger : [];
            $isExistToken           = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->permit_id    = isset($wp_work->wp_permit_id)? $wp_work->wp_permit_id : 0;
            $aproc              = isset($wp_work->aproc)? $wp_work->aproc : 0;
            $dept2              = isset($wp_work->be_dept_id2)? $wp_work->be_dept_id2 : 0;
            $isClose            = isset($wp_work->isClose)? $wp_work->isClose : 'Y';
            $today              = date('Y-m-d');
            //申請日期限制 最多天數
            $limit_day          = sys_param::getParam('PERMIT_APPLY_MAX_DAY');
            $limit_date         = SHCSLib::addDay($limit_day);
            $line               = [];
            $itemwork           = [];
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
            //2.2 已經作廢
            if($isSuc == 'Y' && $isClose === 'Y')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200237';// 該工作許可證已作廢
            }
            //2.3 審查不通過
            if($isSuc == 'Y' && $aproc === 'B')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200267';// 此工單已被審查不通過
            }
            //2.4 此工單已被審查過
            if($isSuc == 'Y' && $aproc != 'A')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200266';// 此工單已被審查過
            }
            //2.6 如拒絕該工作許可證，請填寫事由
            if($isSuc == 'Y' && $this->agree == 'N' && !strlen($this->charge_memo) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200268';// 如拒絕該工作許可證，請填寫事由
            }
            //2.7 請選擇負責「轄區部門」！
            if($isSuc == 'Y' && $this->agree == 'Y' && (!$this->be_dept_id1) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200272';// 請選擇負責「轄區部門」！
            }
            //2.8 危險等級為Ａ級，請選擇負責「監工部門」
            if($isSuc == 'Y' && $this->agree == 'Y' && ($this->wp_permit_danger == 'A' && !$this->be_dept_id3) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200273';// 危險等級為Ａ級，請選擇負責「監工部門」
            }
            //2.9 請填寫「施工日期」
            if($isSuc == 'Y' && $this->agree == 'Y' && !$this->sdate )
            {
                //$isSuc          = 'N';
                //$this->errCode  = 'E00200275';// 請填寫「施工日期」
            }
            //2.10 施工日期不可小於今日
            if($isSuc == 'Y' && $this->agree == 'Y' && $this->sdate && strtotime($this->sdate) < strtotime($today) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200276';// 施工日期不可小於今日
            }
            //2.11 施工日期不可預約:day日後
            if($isSuc == 'Y' && $this->agree == 'Y' && $this->sdate && strtotime($this->sdate) > strtotime($limit_date) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200274';// 施工日期不可預約:day日後
                $this->errParam1= $limit_day;//
            }
            //2.12 不得會簽部門給監造部門
            if($isSuc == 'Y' && $this->agree == 'Y' && $this->be_dept_id4 && $this->be_dept_id4 == $dept2 )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200277';// 不得會簽部門給監造部門
                $this->errParam1= $limit_day;//
            }
            //2.13 請填寫工作地點說明
            if($isSuc == 'Y' && $this->agree == 'Y' && !$this->b_factory_memo )
            {
                //$isSuc          = 'N';
                //$this->errCode  = 'E00200278';// 請填寫工作地點說明
            }
            //2.14 請填寫工作內容
            if($isSuc == 'Y' && $this->agree == 'Y' && !$this->wp_permit_workitem_memo )
            {
                //$isSuc          = 'N';
                //$this->errCode  = 'E00200279';// 請填寫工作內容
            }
            //2.20 請填寫許可工作項目A
            if($isSuc == 'Y' && $this->agree == 'Y' && (!count($this->itemwork)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200269';// 請填寫許可工作項目
            }
            //2.21 請填寫許可工作項目B
            if($isSuc == 'Y' && $this->agree == 'Y')
            {
                $othermemoData = sys_param::getParam('PERMIT_WORKITEM_MEMO_ID',1);
                $othermemoAry  = explode(',',$othermemoData);
                foreach ($this->itemwork as $itemwork_key => $itemwork_val)
                {
                    $itemwork_id = isset($itemwork_val->id)? $itemwork_val->id : 0;
                    $itemwork_memo = isset($itemwork_val->memo)? $itemwork_val->memo : '';
                    if($itemwork_id)
                    {
                        $itemwork[$itemwork_id]  = $itemwork_val;
                        if(in_array($itemwork_id,$othermemoAry) && !strlen($itemwork_memo))
                        {
                            unset($itemwork[$itemwork_id]);
                        }
                    }

                }
                if(!count($itemwork)){
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200269';// 請填寫許可工作項目
                }
            }
            //2.22 請填寫管線或設備之內容物A
            if($isSuc == 'Y' && $this->agree == 'Y' && (!count($this->line)) )
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200270';// 請填寫管線或設備之內容物！
            }
            //2.23 請填寫管線或設備之內容物B
            if($isSuc == 'Y' && $this->agree == 'Y')
            {
                foreach ($this->line as $line_key => $line_val)
                {
                    $line_id = isset($line_val->id)? $line_val->id : 0;
                    $line_memo = isset($line_val->memo)? $line_val->memo : '';
                    if($line_id)
                    {
                        $line[$line_id] = $line_val;
                        if(wp_permit_pipeline::isText($line_id) && !strlen($line_memo))
                        {
                            unset($line[$line_id]);
                        }
                    }

                }
                if(!count($line)){
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200270';// 請填寫管線或設備之內容物
                }
            }
            //3 登入檢核
            if($isSuc == 'Y') {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust = view_user::find($this->b_cust_id);
                $this->bc_type = $this->b_cust->bc_type;

                if ($this->bc_type) {

                    $wp_check = [];
                    if(count($this->wp_check))
                    {
                        foreach ($this->wp_check as $check_val)
                        {
                            $wp_check[] = isset($check_val->id)? $check_val->id : 0;
                        }
                    }
                    $wp_danger = [];
                    if(count($this->wp_danger))
                    {
                        foreach ($this->wp_danger as $danger_val)
                        {
                            $wp_danger[] = isset($danger_val->id)? $danger_val->id : 0;
                        }
                    }


                    $upAry = [];
                    $upAry['aproc']                     = ($this->agree == 'Y')? 'W' : 'B'; //審查通過 ＆審查不通過
                    $upAry['charge_memo']               = $this->charge_memo;
                    if($this->agree == 'Y')
                    {
                        $upAry['itemwork']                  = SHCSLib::toArray($itemwork);
                        $upAry['check']                     = SHCSLib::toArray($wp_check);
                        $upAry['danger']                    = SHCSLib::toArray($wp_danger);
                        $upAry['line']                      = SHCSLib::toArray($line);
                        $upAry['b_factory_a_id']            = $this->b_factory_a_id;
                        $upAry['b_factory_b_id']            = $this->b_factory_b_id;
                        $upAry['b_factory_d_id']            = $this->b_factory_d_id;
                        $upAry['wp_permit_danger']          = $this->wp_permit_danger;
                        $upAry['wp_permit_shift_id']        = $this->wp_permit_shift_id;
                        $upAry['sdate']                     = $this->sdate;
                        $upAry['edate']                     = $this->sdate;
                        $upAry['be_dept_id1']               = $this->be_dept_id1;
                        $upAry['be_dept_id3']               = $this->be_dept_id3;
                        $upAry['be_dept_id4']               = $this->be_dept_id4;
                        $upAry['b_factory_memo']            = $this->b_factory_memo;
                        $upAry['wp_permit_workitem_memo']   = $this->wp_permit_workitem_memo;
                        $upAry['isHoliday']                 = $this->isHoliday;
                        $upAry['isOvertime']                = $this->isOvertime;
                    }
                    //dd($upAry);

                    if ($this->setWorkPermitWorkOrder($this->work_id,$upAry,$this->b_cust_id)) {
                        //已經啟動
                        $this->reply = 'Y';
                        $this->errCode = '';
                    } else {
                        $this->errCode = 'E00200280';// 審查失敗
                    }
                } else {
                    $this->errCode = 'E00200102';// 無法取得帳號資訊
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

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
