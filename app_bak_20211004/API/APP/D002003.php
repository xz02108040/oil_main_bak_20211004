<?php

namespace App\API\APP;

use App\Http\Traits\BcustTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkerTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderCheckTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderItemTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderlineTrait;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
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
 * D002003 工程案件-工作許可證申請查詢.
 * 目的：取得目前正在進行的工作許可證
 *
 */
class D002003 extends JsonApi
{
    use BcustTrait,WorkPermitWorkOrderTrait,WorkPermitWorkerTrait,WorkPermitWorkOrderItemTrait,WorkPermitWorkOrderCheckTrait;
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
            $token              = (isset($jsonObj->token))?         $jsonObj->token : ''; //ＴＯＫＥＮ
            $this->aproc        = (isset($jsonObj->permit_aproc))?  $jsonObj->permit_aproc : '';
            $this->kind         = (isset($jsonObj->wp_permit_kind))?  $jsonObj->wp_permit_kind : '';
            $this->project_id   = (isset($jsonObj->project))?       $jsonObj->project : '';
            $this->sdate        = (isset($jsonObj->sdate))?         $jsonObj->sdate : '';
            $this->supply       = (isset($jsonObj->supply))?        $jsonObj->supply : 0;
            $this->store        = (isset($jsonObj->store))?         $jsonObj->store : 0;
            $this->level        = (isset($jsonObj->level))?         $jsonObj->level : 2;
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
            $aproc      = in_array($this->aproc,['A','P','R','O','C','F']) ? [$this->aproc] : [];
            $isSupply   = 0;
            //承攬商
            if($this->bc_type == 3) {
                $isSupply       = 1;
                $b_supply_id    = b_supply_member::getSupplyId($this->b_cust_id);
            }
            //職員
            else {
                $b_supply_id    = $this->supply;
            }
            $search = [$b_supply_id,$this->project_id,$this->store,$this->sdate,0,0,0,0,'',$isSupply,''];

            if($this->level == 1)
            {
                $ret['data']        = $this->getApiWorkPermitWorkOrderList($search,$aproc,'','N','Y');

            } else {
                $data = $this->getApiWorkPermitWorkOrderList($search,$aproc,'','Y');
                //2019-08-19 需求：統計 待審查/審查/不通過
                if(count($data))
                {
                    $aprocAry = [];
                    foreach ($data as $val)
                    {
                        $danger = $val['kind'];
                        $aproc  = $val['aproc'];
                        //視為通過
                        if(in_array($aproc,['P','K','R','O','F','C']))
                        {
                            $aproc = 'O';
                        }
                        if(!isset($aprocAry[$danger]))          $aprocAry[$danger] = [];
                        if(!isset($aprocAry[$danger]['memo']))  $aprocAry[$danger]['memo'] = '';

                        if(isset($aprocAry[$danger][$aproc]))
                        {
                            $amt = $aprocAry[$danger][$aproc];
                            $aprocAry[$danger][$aproc] = $amt + 1;
                        } else {
                            $aprocAry[$danger][$aproc] = 1;
                        }
                    }
                    if(count($aprocAry))
                    {
                        foreach ($aprocAry as $danger => $val)
                        {
                            if(isset($aprocAry[$danger]))
                            {
                                $amt1 = isset($val['A'])? $val['A'] : 0;
                                $amt2 = isset($val['O'])? $val['O'] : 0;
                                $amt3 = isset($val['B'])? $val['B'] : 0;
                                $aprocAry[$danger]['memo'] = Lang::get(Lang::get('sys_base.base_10140',['name1'=>$amt1,'name2'=>$amt2,'name3'=>$amt3]));
                            }
                        }
                    }
                    foreach ($data as $key => $val)
                    {
                        $danger = $val['kind'];
                        $aproc  = $val['aproc'];
                        if(isset($aprocAry[$danger]))
                        {
                            //視為通過
                            if(in_array($aproc,['P','K','R','O','F','C']))
                            {
                                $aproc = 'O';
                            }

                            if(isset($aprocAry[$danger][$aproc]))
                            {
                                $data[$key]['sub_title'] = $aprocAry[$danger]['memo'];
                            }
                        }
                    }
                }
                $ret['permit_work'] = $data;

            }
        }
        //執行時間
        $ret['runtime'] = $this->getRunTime();

        return $ret;
    }

}
