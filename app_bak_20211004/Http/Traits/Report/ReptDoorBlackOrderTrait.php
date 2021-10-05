<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Emp\be_title;
use App\Model\Report\rept_doorinout_day;
use App\Model\Report\rept_doorinout_time;
use App\Model\Supply\b_supply;
use App\Model\Supply\b_supply_member;
use App\Model\sys_param;
use App\Model\View\view_door_supply_whitelist_pass;
use App\Model\View\view_door_work_whitelist;
use App\Model\View\view_supply_violation;
use App\Model\View\view_used_rfid;
use Storage;
use DB;
use Lang;
use Session;

/**
 * 報表：門禁黑白名單
 *
 */
trait ReptDoorBlackOrderTrait
{
    /**
     * 取得 門禁黑白名單
     * $showType 1:黑白名單明細 2:[黑白名單(ID+Name)Ary，白名單Ary，不在工單Ary，工程資格失效Ary，黑白名單明細Ary] 3: 只保留所有違規
     */
    public function getDoorBlackOrderList($showType = 1)
    {
        $whitelistAry = $whitelistAry1 = $whitelistAry2 = $whitelistAry3 = [];
        //違規
        [$violationAry,$violationDetailAry]   = view_supply_violation::getSelect();
        //dd($violationAry,$violationDetailAry);
        //白名單
        [$whitelistAry1,$whitelistDetailAry]   = view_door_work_whitelist::getSelect();
        if(in_array($showType,[1,3]) && count($whitelistDetailAry)){
            //只列入違規的人員
            if($showType == 3)
            {
                foreach ($whitelistDetailAry as $uid => $val)
                {
                    if(!in_array($uid,$violationAry)) unset($whitelistDetailAry[$uid]);
                }
            }
            //白名單: 檢查是否違規
            foreach ($whitelistDetailAry as $uid => $val)
            {
                $tmp = $val;
                //如果有違規
                if(in_array($uid,$violationAry))
                {
                    $violation = isset($violationDetailAry[$uid])? $violationDetailAry[$uid]['violation_record4'] : '';
                    $limit     = isset($violationDetailAry[$uid])? $violationDetailAry[$uid]['limit_edate'] : '';
                    $tmp['err_code'] = 3;
                    $tmp['err_memo'] = $violation.'('.$limit.')';
                }
                $whitelistDetailAry[$uid] = $tmp;
            }
        }
        //dd($whitelistAry1,$whitelistDetailAry);
        //黑名單: 當日沒有工單
        $data1 = view_door_supply_whitelist_pass::whereNotIn('b_cust_id',$whitelistAry1);
        if($data1->count())
        {
            foreach ($data1->get() as $val)
            {
                //2020-09-18增加  取得門禁人員ID並找出對應承攬商，判斷該承攬商有無停用或啟用綁工當進出
                $Chk_WorkClose	= b_supply::getWorkClose($val->b_supply_id);

                $tmp = [];
                $tmp['name']        = $val->name;
                $tmp['supply']      = $val->supply;
                $tmp['b_cust_id']   = $val->b_cust_id;
                $tmp['mobile1']     = $val->mobile1;
                $tmp['head_img_at'] = $val->head_img_at;
                $tmp['head_img']    = $val->head_img;
                $tmp['rfid_code']   = $val->rfid_code;
                //如果沒有啟用工程綁定
                if($Chk_WorkClose=='N')
                {
                    $tmp['err_code']    = 1;
                    $tmp['err_memo']    = '';
                }
                else
                {
                    $tmp['err_code']    = 0;
                    $tmp['err_memo']    = '';
                    $whitelistAry1[] = $val->b_cust_id;
                }

                if($showType == 3)
                {
                    $tmp['supply']  = $val->supply;
                }
                $whitelistDetailAry[$val->b_cust_id] = $tmp;
                $whitelistAry2[] = $val->b_cust_id;
            }
        }
        $whitelistAry = array_merge($whitelistAry1,$whitelistAry2);
        //dd($whitelistAry1,$whitelistAry2,$whitelistAry);

        //黑名單:工程資格不符
        $data2 = view_used_rfid::join('view_door_supply_member as u','u.b_cust_id','=','view_used_rfid.b_cust_id')->
                    where('view_used_rfid.rfid_type',5)->whereNotIn('view_used_rfid.b_cust_id',$whitelistAry)->
                    select('u.*','rfid_code');
        //dd($b_factory_id,$sdate,$edate,$b_supply_id,$uid,$data->count());
        if($data2->count())
        {
            foreach ($data2->get() as $val)
            {
                $tmp = [];
                $tmp['name']        = $val->name;
                $tmp['b_cust_id']   = $val->b_cust_id;
                $tmp['mobile1']     = $val->mobile1;
                $tmp['head_img_at'] = $val->head_img_at;
                $tmp['head_img']    = $val->head_img;
                $tmp['rfid_code']   = $val->rfid_code;
                $tmp['err_code']    = 2;
                $tmp['err_memo']    = '';
                if($showType == 3)
                {
                    $tmp['supply']  = $val->supply;
                }
                $whitelistDetailAry[$val->b_cust_id] = $tmp;
                $whitelistAry3[] = $val->b_cust_id;
            }
        }
        if(count($whitelistDetailAry))
        {
            foreach ($whitelistDetailAry as $uid => $val)
            {
                $whitelistDetailAry[$uid] = (object)$val;
            }
        }

        return ($showType == 2)? [$whitelistAry,$whitelistAry1,$whitelistAry2,$whitelistAry3,$whitelistDetailAry] : $whitelistDetailAry;
    }


}
