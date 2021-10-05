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
use App\Http\Traits\WorkPermit\WorkRPTranUserDetailTrait;
use App\Http\Traits\WorkPermit\WorkRPTranUserTrait;
use App\Lib\HTTCLib;
use App\Model\User;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\WorkPermit\wp_work;
use App\Model\WorkPermit\wp_work_rp_tranuser;
use App\Model\WorkPermit\wp_work_rp_tranuser_a;
use App\Model\WorkPermit\wp_work_worker;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002131 [工作許可證]-工單轉單申請單.
 * 目的：申請 .工單轉單申請單
 *
 */
class D002131 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitWorkOrderProcessTrait,WorkPermitProcessTopicTrait,WorkPermitTopicOptionTrait;
    use WorkPermitWorkTopicOptionTrait,WorkPermitWorkTopicTrait,WorkPermitDangerTrait;
    use WorkCheckTrait,WorkCheckTopicTrait,WorkCheckTopicOptionTrait;
    use WorkPermitCheckTopicOptionTrait,WorkPermitCheckTopicTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,PushTraits;
    use WorkPermitWorkOrderlineTrait,WorkRPTranUserTrait,WorkRPTranUserDetailTrait;
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
        $today              = date('Y-m-d');
        //格式檢查
        if(isset($jsonObj->token))
        {
            //1.1 參數資訊
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $jsonData           = (isset($jsonObj->data))?          $jsonObj->data : '';
            $this->work_id      = (isset($jsonData[0]->work_id))?   $jsonData[0]->work_id : '';
            $this->work_id2     = (isset($jsonData[0]->work_id2))?      $jsonData[0]->work_id2 : '';
            $this->apply_memo   = (isset($jsonData[0]->apply_memo))?    $jsonData[0]->apply_memo : '';
            $this->tran_user    = (isset($jsonData[0]->tran_user))?     $jsonData[0]->tran_user : [];
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            $wp_work            = wp_work::getData($this->work_id);
            $this->aproc        = isset($wp_work->aproc)? $wp_work->aproc : '';
            $this->e_project_id = isset($wp_work->e_project_id)? $wp_work->e_project_id : 0;
            $this->b_supply_id  = isset($wp_work->b_supply_id)? $wp_work->b_supply_id : 0;
            $this->work_date    = isset($wp_work->sdate)? $wp_work->sdate : '';
            $this->charge_dept1 = isset($wp_work->be_dept_id2)? $wp_work->be_dept_id2 : 0;
            $supply_worker      = wp_work_worker::getSelect($this->work_id,1,0,0);
            $supply_safer       = wp_work_worker::getSelect($this->work_id,2,0,0);
            $this->workerAry    = array_merge($supply_worker,$supply_safer);
            $isApplyExist       = wp_work_rp_tranuser::isExist($this->work_id);


            $wp_work2           = wp_work::getData($this->work_id2);
            $this->aproc2       = isset($wp_work2->aproc)? $wp_work2->aproc : '';
            $this->work_date2   = isset($wp_work2->sdate)? $wp_work2->sdate : '';

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }elseif($isSuc == 'Y' && (!$this->work_id))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200288';// 請選擇工單
            }elseif($isSuc == 'Y' && (!$this->apply_memo))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200289';// 請填寫延長事由
            }elseif($isSuc == 'Y' && ((is_array($this->tran_user) && !count($this->tran_user)) || !$this->tran_user))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200339';// 請填寫延長時間
            }elseif($isSuc == 'Y' && (!$this->aproc || !$this->aproc2 || !$this->e_project_id || !$this->work_date))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200291';// 無此工單，請聯絡資訊處理
            }elseif($isSuc == 'Y' && $this->aproc != 'R')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200293';// 該工單並非在施工階段
            }elseif($isSuc == 'Y' && $this->work_date != $today)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200292';// 僅現今日施工階段的工單才能申請
            }elseif($isSuc == 'Y' && $this->aproc2 != 'R')
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200340';// 欲轉入單需在【待啟動】【檢點】【施工】階段內
            }elseif($isSuc == 'Y' && $this->work_date2 != $today)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200341';// 欲轉入單，僅現今日工單才能申請
            }elseif($isSuc == 'Y' && $isApplyExist)
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200342';// 該工單正在申請【轉單】，請勿重複申請
            } else {
                $errMsg = [];
                foreach ($this->tran_user as $val)
                {
                    $chkTranUser = wp_work_worker::chkTranUserInfo($this->work_id,$val->b_cust_id);
                    $name        = User::getName($val->b_cust_id);
                    if($chkTranUser == 0)
                    {
                        $errMsg[] = Lang::get('sys_api.E00200343',['name'=>$name]);
                    }
                    if($chkTranUser < 0)
                    {
                        $errMsg[] = Lang::get('sys_api.E00200344',['name'=>$name]);
                    }
                }
                if(count($errMsg))
                {
                    $isSuc          = 'N';
                    $this->errCode  = 'E00200345';//申請對象資格不符
                    $this->errParam1= implode('，',$errMsg);
                }
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->apiKey    = $isExistToken->apiKey;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if($this->bc_type == 3)
                {
                    if(!in_array($this->b_cust_id,$this->workerAry))
                    {
                        $this->errCode  = 'E00200301';// 僅限工安&工負申請
                    } else {
                        $INS = [];
                        $INS['wp_work_id']      = $this->work_id;
                        $INS['wp_work_id2']     = $this->work_id2;
                        $INS['e_project_id']    = $this->e_project_id;
                        $INS['b_supply_id']     = $this->b_supply_id;
                        $INS['work_date']       = $this->work_date;
                        $INS['charge_dept1']    = $this->charge_dept1;
                        $INS['tran_user']       = $this->tran_user;
                        $INS['apply_memo']      = $this->apply_memo;
                        if($this->createWorkRPTranUserTrait($INS,$this->b_cust_id))
                        {
                            $this->reply     = 'Y';
                            $this->errCode   = '';
                        } else {
                            $this->errCode  = 'E00200282';// 申請失敗，請聯絡管理者
                        }
                    }
                } elseif($this->bc_type != 3)
                {
                    $this->errCode  = 'E00200295';// 僅開放承攬商申請
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

        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
