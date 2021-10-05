<?php

namespace App\Http\Traits\Report;

use App\Lib\ContentLib;
use App\Lib\FormLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Report\rept_doorinout_day;
use App\Model\Report\rept_doorinout_time;
use App\Model\Supply\b_supply;
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
trait ReptDoorOrderTrait
{
    /**
     * 取得 門禁黑白名單
     * $showType 1:黑白名單明細 2:[黑白名單(ID+Name)Ary，白名單Ary，不在工單Ary，工程資格失效Ary，黑白名單明細Ary] 3: 只保留所有違規
     */
    public function getDoorMenOrderList()
    {
        $data1 = DB::table('view_door_supply_whitelist_pass')->select('name','b_cust_id','supply','project','project_no','rfid_code')->get()->toArray();
        $data2 = DB::table('view_door_blacklist')->select('name','unit_id as b_cust_id','unit as supply','project_name as project','project_no','rfid_code')->where('error',1)->get()->toArray();
        return array_merge($data1,$data2);
    }
    /**
     * 取得 門禁黑白名單
     * $showType 1:黑白名單明細 2:[黑白名單(ID+Name)Ary，白名單Ary，不在工單Ary，工程資格失效Ary，黑白名單明細Ary] 3: 只保留所有違規
     */
    public function getDoorCarOrderList()
    {
        return DB::table('view_door_car')->select('car_no','supply','project','project_no','oil_kind','isInspectionExhaust','last_exhaust_inspection_date2')->get()->toArray();
    }


}
