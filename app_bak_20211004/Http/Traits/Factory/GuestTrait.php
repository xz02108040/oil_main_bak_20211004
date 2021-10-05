<?php

namespace App\Http\Traits\Factory;

use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_factory_b;
use App\Model\Factory\b_factory_e;
use App\Model\Factory\b_guest;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * 廠區_轄區部門
            *
 */
trait GuestTrait
{
    /**
     * 新增 廠區_轄區部門
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createGuest($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->guest_comp)) return $ret;

        $INS = new b_guest();
        $INS->b_rfid_id      = isset($data->b_rfid_id) ? $data->b_rfid_id : 0;
        $INS->b_factory_id   = isset($data->b_factory_id) ? $data->b_factory_id : 0;
        $INS->guest_no       = isset($data->guest_no) ? $data->guest_no : '';
        $INS->guest_comp     = isset($data->guest_comp) ? $data->guest_comp : '';
        $INS->guest_name     = isset($data->guest_name) ? $data->guest_name : '';
        $INS->guest_tel      = isset($data->guest_tel) ? $data->guest_tel : '';
        $INS->gruest_id      = isset($data->gruest_id) ? $data->gruest_id : '';
        $INS->visit_dept     = isset($data->visit_dept) ? $data->visit_dept : '';
        $INS->visit_emp      = isset($data->visit_emp) ? $data->visit_emp : '';
        $INS->visit_date     = isset($data->visit_date) ? $data->visit_date : date('Y-m-d');
        $INS->visit_sdate    = isset($data->visit_sdate) ? $data->visit_sdate : date('Y-m-d H:i:s');
        $INS->visit_purpose  = isset($data->visit_purpose) ? $data->visit_purpose : '';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 廠區_轄區部門
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setGuest($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = b_guest::find($id);
        if(!isset($UPD->guest_comp)) return $ret;
        //廠區
        if(isset($data->guest_comp) && strlen($data->guest_comp) && $data->guest_comp !==  $UPD->guest_comp)
        {
            $isUp++;
            $UPD->guest_comp = $data->guest_comp;
        }

        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose       = 'Y';
                $UPD->close_user    = $mod_user;
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
     * 取得 廠區_轄區部門
     *
     * @return array
     */
    public function getApiGuestList($store_id ,$sdate = '',$isClose = '')
    {
        $ret = array();
        //取第一層
        $data = b_guest::where('b_factory_id',$store_id)->orderby('isClose')->orderby('id','desc');
        if($sdate)
        {
            $data = $data->where('visit_date',$sdate);
        }
        if($isClose)
        {
            $data = $data->where('isClose',$isClose);
        }
        $data = $data->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {

            }
            $ret = (object)$data;
        }

        return $ret;
    }
}
