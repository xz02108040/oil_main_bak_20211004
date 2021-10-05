<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\Emp\EmpTrait;
use App\Http\Traits\Engineering\EngineeringCarTrait;
use App\Http\Traits\Engineering\EngineeringCourseTrait;
use App\Http\Traits\Engineering\EngineeringDeptTrait;
use App\Http\Traits\Engineering\EngineeringFactoryTrait;
use App\Http\Traits\Engineering\EngineeringHistoryTrait;
use App\Http\Traits\Engineering\EngineeringLicenseTrait;
use App\Http\Traits\Engineering\EngineeringMemberTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Factory\FactoryDeptTrait;
use App\Http\Traits\Factory\FactoryDeviceTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Lib\SHCSLib;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\API\JsonApi;
use App\Lib\CheckLib;
use App\Lib\TokenLib;
use App\Model\View\view_dept_member;
use App\Model\View\view_user;
use Auth;
use Lang;
use Session;
use UUID;
/**
 * D002001 工程案件.
 * 目的：取得目前正在負責的工程案件
 *
 */
class D002001 extends JsonApi
{
    use BcustTrait,EngineeringTrait,EngineeringFactoryTrait,EngineeringMemberTrait,EngineeringCourseTrait,EngineeringLicenseTrait;
    use EngineeringDeptTrait,EngineeringHistoryTrait,EngineeringCarTrait;
    use FactoryDeviceTrait,FactoryDeptTrait,EmpTrait;
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
            $token              = (isset($jsonObj->token))? $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->aproc        = (isset($jsonObj->project_aproc))? $jsonObj->project_aproc : '';
            $this->sdate        = (isset($jsonObj->sdate))? $jsonObj->sdate : '';
            $this->edate        = (isset($jsonObj->edate))? $jsonObj->edate : '';
            $this->type         = (isset($jsonObj->project_type))? $jsonObj->project_type : '';
            $this->no           = (isset($jsonObj->no))?    $jsonObj->no : '';
            $this->supply       = (isset($jsonObj->supply))?$jsonObj->supply : 0;
            $this->store        = (isset($jsonObj->store))? $jsonObj->store : 0;
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
            //承攬商
            if($this->bc_type == 3)
            {
                $b_supply_id        = b_supply_member::getSupplyId($this->b_cust_id);
                $myDeptId           = 0;
            }
            //職員
            else {
                $b_supply_id = $this->supply;
                $RootDept    = sys_param::getParam('ROOT_CHARGE_DEPT',1);
                $be_dept_id  = view_dept_member::getDept($this->b_cust_id);
                $isRootDept  = ($RootDept == $be_dept_id)? true : false;
                $myDeptId    = $isRootDept? 0 : $be_dept_id;
            }
            $searchAry       = [$b_supply_id,$this->store,$this->type,$this->aproc,$this->no,$this->sdate,$this->edate,$myDeptId];
            $ret['data']     = $this->getApiEngineeringList($searchAry,'N','','Y');


        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
