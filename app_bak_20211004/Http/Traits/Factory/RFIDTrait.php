<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_invalid_type;
use App\Model\Factory\b_rfid_type;
use App\Model\Supply\b_supply;
use App\Model\User;
use DB;

/**
 * RFID
 *
 */
trait RFIDTrait
{
    /**
     * 新增 RFID
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createRFID($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->rfid_code)) return $ret;

        $INS = new b_rfid();
        $INS->name          = $data->name;
        $INS->rfid_code     = $data->rfid_code;
        $INS->rfid_type     = $data->rfid_type;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 RFID
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setRFID($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_rfid::find($id);
        if(!isset($UPD->id)) return $ret;
        //卡片代碼
        if(isset($data->name) && $data->name && $data->name !== $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //卡片內容
        if(isset($data->rfid_code) && $data->rfid_code && $data->rfid_code !== $UPD->rfid_code)
        {
            $isUp++;
            $UPD->rfid_code = $data->rfid_code;
        }
        //卡片內容
        if(isset($data->rfid_type) && $data->rfid_type && $data->rfid_type !== $UPD->rfid_type)
        {
            $isUp++;
            $UPD->rfid_type = $data->rfid_type;
        }
        //卡片內容
        if(isset($data->rfid_invalid_type) && $data->rfid_invalid_type && $data->rfid_invalid_type !== $UPD->rfid_invalid_type)
        {
            $isUp++;
            $UPD->rfid_invalid_type = $data->rfid_invalid_type;
        }
        //配對
        if(isset($data->b_rfid_a_id) && $data->b_rfid_a_id >= 0 && $data->b_rfid_a_id !== $UPD->b_rfid_a_id)
        {
            $isUp++;
            $UPD->b_rfid_a_id     = $data->b_rfid_a_id;
            if($data->b_rfid_a_id > 0)
            {
                $UPD->isUsed           = 'Y';
                $UPD->paircard_user    = $mod_user;
                $UPD->paircard_stamp   = $now;
            } else {
                $UPD->isUsed      = 'N';
            }
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
                $UPD->close_memo    = isset($data->close_memo)? $data->close_memo : '';
                $UPD->close_stamp   = $now;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret = $UPD->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 RFID
     *
     * @return array
     */
    public function getApiRFIDList($type,$isused = '',$isClose = 'N',$supplyId = 0, $userName = '', $rfidcode = '')
    {
        // 未選類型/姓名/卡片內碼 或 類型為承攬商卡且為使用中但未選擇承攬商 則不回傳資料
        if ((!$type && !$userName && !$rfidcode) || ($type == 5 && $isused !== 'N' && empty($supplyId) && empty($userName))) return [];
       
        $ret = array();
        $typeAry        = b_rfid_type::getSelect();
        $invalidAry     = b_rfid_invalid_type::getSelect();
        $nationAry      = SHCSLib::getCode('NATION_TYPE');
        //取第一層
        $data = b_rfid::select('b_rfid.id', 'b_rfid.id', 'b_rfid_a.b_cust_id', 'b_rfid.rfid_type', 'b_rfid.rfid_code', 'b_rfid_a.id as b_rfid_a_id', 'b_rfid_a.isClose', 'b_rfid_a.sdate', 'b_rfid_a.edate', 'b_cust.name','b_cust.nation', 'b_cust_a.bc_id', DB::raw("ISNULL(b_rfid.isUsed,'') AS isUsed"))
        ->join('b_rfid_a', 'b_rfid_a.b_rfid_id', 'b_rfid.id')
        ->join('b_cust', 'b_rfid_a.b_cust_id', 'b_cust.id')
        ->leftjoin('b_cust_a', 'b_cust_a.b_cust_id', 'b_cust.id');
        if($type)
        {
            $data = $data->where('b_rfid.rfid_type',$type);
        }
        if($isused)
        {
            $data = $data->where('b_rfid.isUsed',$isused);
        }
        if($isClose && empty($userName)) // 若有查詢人名時不論啟用/停用都顯示出來
        {
            $data = $data->where('b_rfid_a.isClose',$isClose);
        }
        if(!empty($supplyId)){
            $data = $data->where('b_rfid_a.b_supply_id',$supplyId);
        }
        if (!empty($userName)) {
            $data = $data->where('b_cust.name', 'like', "%$userName%");
        }
        if (!empty($rfidcode)) {
            $data = $data->where('b_rfid.rfid_code', '=', $rfidcode);
        }
        $data = $data->orderby('b_rfid.isClose')->orderby('b_rfid.id','b_rfid.desc')->get();
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['rfid_type_name']     = isset($typeAry[$v->rfid_type])? $typeAry[$v->rfid_type] : '';
                $data[$k]['nation_name']        = isset($nationAry[$v->nation])? $nationAry[$v->nation] : '';
                $data[$k]['invalid_name']       = isset($invalidAry[$v->b_rfid_invalid_type])? $invalidAry[$v->b_rfid_invalid_type] : '';
                $data[$k]['paircard_user_name'] = User::getName($v->paircard_user);
                $data[$k]['close_user']         = User::getName($v->close_user);
                $data[$k]['new_user']           = User::getName($v->new_user);
                $data[$k]['mod_user']           = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
