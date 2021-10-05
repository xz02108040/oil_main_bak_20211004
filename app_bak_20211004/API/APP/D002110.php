<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
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
use App\Model\Emp\b_cust_e;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use App\Lib\SHCSLib;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002110 [工作許可證]-尚未審查.
 * 目的：取得　當日工作許可證
 *
 */
class D002110 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait;
    use WorkPermitWorkOrderlineTrait,WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderCheckTrait;
    use WorkPermitWorkItemDangerTrait,WorkPermitWorkOrderDangerTrait;
    use WorkPermitProcessTrait,WorkPermitProcessTargetTrait;
    use WorkPermitProcessTopicTrait,WorkPermitWorkProcessTopicOption,WorkPermitTopicOptionTrait;
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
            $this->danger       = (isset($jsonObj->wp_permit_danger))?  $jsonObj->wp_permit_danger : '';
            $this->project_id   = (isset($jsonObj->project))?       $jsonObj->project : '';
            $this->sdate        = date('Y-m-d');
            $this->shift        = (isset($jsonObj->shift))?         $jsonObj->shift : 0;
            $this->supply       = (isset($jsonObj->supply))?        $jsonObj->supply : 0;
            $this->store        = (isset($jsonObj->store))?         $jsonObj->store : 0;
            $this->level        = (isset($jsonObj->level))?         $jsonObj->level : 1;
            $this->permit_no    = (isset($jsonObj->qrocde))?         $jsonObj->qrocde : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);

            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {

                $this->b_cust_id = isset($isExistToken->b_cust_id)? $isExistToken->b_cust_id : 0;
                $this->apiKey = isset($isExistToken->apiKey)? $isExistToken->apiKey : 0;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;

                if(isset($this->bc_type) && $this->bc_type == 2)
                {
                    $this->reply     = 'Y';
                    $this->errCode   = '';
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
            //承攬商
            $b_supply_id    = $this->supply;
            $memberObj      = view_dept_member::getData($this->b_cust_id);
            $deptid         = isset($memberObj->be_dept_id)? $memberObj->be_dept_id : 0;
            $titleid        = isset($memberObj->be_title_id)? $memberObj->be_title_id : 0;
            $storeid        = isset($memberObj->b_factory_id)? $memberObj->b_factory_id : 0;
            //廠區主簽者
            if($titleid == 4)
            {
                if(!$this->store) $this->store = $storeid; //如果沒有指定廠區，則限定自己負責的廠區
                if($storeid == $this->store) $deptid = 0;  //負責整廠的工單
            }

            $aproc      = ['A'];
            $wpSearch   = [$b_supply_id,$this->project_id,$this->permit_no,$this->danger,$this->shift];
            $storeSearch= [0,0,0];
            $depSearch  = [0,0,$deptid,0,0,0];
            $dateSearch = ['','','Y'];
            //dd($isSupply,$this->aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch);

            if($this->level == 1)
            {
                $data = $this->getApiWorkPermitWorkOrderList(0,$aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch,['N',0],'Y');
            } else {
                $data = $this->getApiWorkPermitWorkOrderList(0,$aproc,$wpSearch,$storeSearch,$depSearch,$dateSearch,['Y',$this->b_cust_id]);
            }
            $ret['data'] = $data;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
