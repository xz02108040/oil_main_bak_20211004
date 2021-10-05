<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitCheckTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTopicTrait;
use App\Http\Traits\WorkPermit\WorkPermitProcessTrait;
use App\Http\Traits\WorkPermit\WorkPermitTopicOptionTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderListTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkProcessTopicOption;
use App\Model\Factory\b_factory_e;
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
 * D002011 工作許可證[啟動階段] 尚未執行
 * 承商身份：取得 「已啟動」->「回簽」
 * 監造身份：取得 「尚未啟動」-> 「啟動」
 *
 */
class D002011 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderItemTrait;
    use WorkPermitWorkOrderCheckTrait,WorkPermitWorkOrderListTrait;
    use WorkPermitProcessTopicTrait,WorkPermitWorkProcessTopicOption;
    use WorkPermitCheckTopicTrait,WorkPermitCheckTopicOptionTrait,WorkPermitTopicOptionTrait,WorkCheckTopicTrait;
    use WorkCheckTrait,WorkCheckTopicOptionTrait,WorkPermitProcessTrait;
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
            $token              = (isset($jsonObj->token))?      $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->project_id   = (isset($jsonObj->project))?    $jsonObj->project : '';
            $this->prmit_no     = (isset($jsonObj->prmit_no))?   $jsonObj->prmit_no : '';
            $this->aproc        = (isset($jsonObj->list_aproc))? $jsonObj->list_aproc : '';
            $this->aproc2       = (isset($jsonObj->permit_aproc))? $jsonObj->permit_aproc : '';
            $this->danger       = (isset($jsonObj->wp_permit_danger))? $jsonObj->wp_permit_danger : '';
            $this->sdate        = (isset($jsonObj->sdate))?             $jsonObj->sdate : '';
            $isExistToken       = TokenLib::isTokenExist(0, $token,$this->tokenType);
            //dd($jsonObj);
            //2.1 帳號/密碼不可為空
            if(!isset($isExistToken->token))
            {
                $isSuc          = 'N';
                $this->errCode  = 'E00200101';// 請重新登入
            }
            //3 登入檢核
            if($isSuc == 'Y')
            {
                $this->b_cust_id = $isExistToken->b_cust_id;
                $this->b_cust    = view_user::find($this->b_cust_id);
                $this->bc_type   = $this->b_cust->bc_type;
                if(!$this->sdate || !CheckLib::isDate($this->sdate)) $this->sdate = date('Y-m-d');
                if(mb_strlen($this->prmit_no) == 4) $this->prmit_no = mb_substr($this->prmit_no,0,2);

                if(isset($this->bc_type))
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
            $aproc = $aproc2 = [];
            if(!$this->aproc2)
            {
                if($this->aproc)
                {
                    $aproc = [$this->aproc];
                } else {
                    $aproc = ['A','P','R','O'];
                }
            } else {
                if($this->aproc == 'K' || $this->aproc == 'P')
                {
                    $aproc2 = ['P','K','R','O'];
                } else {
                    $aproc2 = [$this->aproc2];
                }
            }

            //承攬商
            if($this->bc_type == 3) {
                $b_supply_id    = b_supply_member::getSupplyId($this->b_cust_id);

                //找到《啟動階段》-需要回簽的工作許可證
                $search = [$b_supply_id,$this->project_id,0,$this->sdate,0,0,0,0,$aproc,1,$this->prmit_no];
                $rept   = $this->getApiWorkPermitWorkOrderList($search,$aproc2,'','Y');
            }
            //職員
            else {
                $b_supply_id    = 0;
                //2019-10-02 開放搜尋非自己部門的 工作許可證
                $dept_id        = view_dept_member::getDept($this->b_cust_id);
                $dept_factory   = b_factory_e::getStoreAry($dept_id);
                $ret['dept_id'] = $dept_id;
                //找到自己部門負責的《啟動階段》-需要啟動的工作許可證
                $search = [$b_supply_id,$this->project_id,$dept_factory,$this->sdate,0,$dept_id,0,0,$aproc,0,$this->prmit_no];
                $rept   = $this->getApiWorkPermitWorkOrderList($search,$aproc2,'','Y');
            }
            //找到當日正在執行的工作許可證
            $ret['permit_work'] = $rept;

            //巡邏會簽-題目
            $patrolData         = $this->getApiWorkPermitProcessTopic(1,7,0,'',[9],'Y');
            $ret['patrol']      = $patrolData ;
            $ret['search']      = $search;
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
