<?php

namespace App\Http\Traits\Factory;

use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\Emp\b_cust_e;
use App\Model\Emp\be_dept;
use App\Model\Emp\be_title;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Factory\b_rfid;
use App\Model\Factory\b_rfid_a;
use App\Model\Factory\b_rfid_invalid_type;
use App\Model\Supply\b_supply;
use App\Model\User;

/**
 * RFID 配對
 *
 */
trait RFIDPairTrait
{
    /**
     * 新增 RFID配對
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createRFIDPair($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->b_rfid_id)) return $ret;
        $useid  = b_rfid::getUsedId($data->b_rfid_id);

        $INS = new b_rfid_a();
        $INS->b_rfid_id         = $data->b_rfid_id;
        $INS->b_factory_id      = $data->b_factory_id ? $data->b_factory_id : 0;
        $INS->b_factory_a_id    = isset($data->b_factory_a_id) ? $data->b_factory_a_id : 0;
        $INS->be_dept_id        = isset($data->be_dept_id) ? $data->be_dept_id : 0;
        $INS->b_cust_id         = isset($data->b_cust_id) ? $data->b_cust_id : 0;
        $INS->b_supply_id       = isset($data->b_supply_id) ? $data->b_supply_id : 0;
        $INS->e_project_id      = isset($data->e_project_id) ? $data->e_project_id : 0;
        $INS->b_car_id          = isset($data->b_car_id) ? $data->b_car_id : 0;
        $INS->sdate             = (isset($data->sdate) && $data->sdate) ? $data->sdate : date('Y-m-d H:i:s');
        $INS->edate             = (isset($data->edate) && $data->edate) ? $data->edate : '9999-12-31 00:00:00';

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        //配對成功
        if($ret)
        {
            //2-1 取消：目前配對
            $UPD = [];
            $UPD['edate']   = $INS->sdate;
            $UPD['isClose'] = 'Y';
            $this->setRFIDPair($useid,$UPD,$mod_user);
            //2-2 回寫：目前配對
            $UPD = [];
            $UPD['b_rfid_a_id'] = $ret;
            $this->setRFID($data->b_rfid_id,$UPD,$mod_user);
            //2-3 回寫:解除鎖定
            LogLib::setLogPairCardLock($INS->b_supply_id,$INS->b_cust_id,'P','1000000001');
        }

        return $ret;
    }

    /**
     * 修改 RFID配對
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setRFIDPair($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = $isClose = 0;

        $UPD = b_rfid_a::find($id);
        if(!isset($UPD->b_rfid_id)) return $ret;
        //廠區
        if(isset($data->b_factory_id) && is_numeric($data->b_factory_id) && $data->b_factory_id !==  $UPD->b_factory_id)
        {
            $isUp++;
            $UPD->b_factory_id = $data->b_factory_id;
        }
        //廠區
        if(isset($data->b_factory_a_id) && is_numeric($data->b_factory_a_id) && $data->b_factory_a_id !==  $UPD->b_factory_a_id)
        {
            $isUp++;
            $UPD->b_factory_a_id = $data->b_factory_a_id;
        }
        //人員
        if(isset($data->be_dept_id) && is_numeric($data->be_dept_id) && $data->be_dept_id !==  $UPD->be_dept_id) {
            $isUp++;
            $UPD->be_dept_id = $data->be_dept_id;
        }
        //人員
        if(isset($data->b_cust_id) && is_numeric($data->b_cust_id) && $data->b_cust_id !==  $UPD->b_cust_id)
        {
            $isUp++;
            $UPD->b_cust_id = $data->b_cust_id;
        }
        //承攬商
        if(isset($data->b_supply_id) && is_numeric($data->b_supply_id) && $data->b_supply_id !==  $UPD->b_supply_id)
        {
            $isUp++;
            $UPD->b_supply_id = $data->b_supply_id;
        }
        //車輛通行證ＩＤ
        if(isset($data->b_car_id) && is_numeric($data->b_car_id) && $data->b_car_id !==  $UPD->b_car_id)
        {
            $isUp++;
            $UPD->b_car_id = $data->b_car_id;
        }
        //啟用日期
        if(isset($data->sdate) && ($data->sdate) && $data->sdate !==  $UPD->sdate)
        {
            $isUp++;
            $UPD->sdate = $data->sdate;
        }
        //結束日期
        if(isset($data->edate) && ($data->edate) && $data->edate !==  $UPD->edate)
        {
            $isUp++;
            $UPD->edate = $data->edate;
        }
        //作廢
        if(isset($data->isClose) && in_array($data->isClose,['Y','N']) && $data->isClose !==  $UPD->isClose)
        {
            $isUp++;
            //解除配對
            if($data->isClose == 'Y')
            {
                $UPD->isClose           = 'Y';
                $UPD->rfid_invalid_type = isset($data->rfid_invalid_type)? $data->rfid_invalid_type : 1;
                $UPD->close_user        = $mod_user;
                $UPD->close_stamp       = $now;
                $UPD->edate             = $now;
                $isClose = 1;
            } else {
                $UPD->isClose = 'N';
            }
        }
        if($isUp)
        {
            $UPD->mod_user = $mod_user;
            $ret    = $UPD->save();
            //取得 目前正在生效的配對ＩＤ
            $useid  = b_rfid::getUsedId($UPD->b_rfid_id);
            //如果符合目前使用中的配對ＩＤ 而且 取消配對，則釋放 該ＲＦＩＤ卡片
            if($ret && $isClose && $useid == $id)
            {
                $rfid_invalid_type = isset($data->rfid_invalid_type)? $data->rfid_invalid_type : 1;
                $UPD2 = [];
                $UPD2['b_rfid_a_id'] = 0;
                $UPD2['rfid_invalid_type']  = $rfid_invalid_type;
                $UPD2['isClose']            = b_rfid_invalid_type::getIsInvalidRfid($rfid_invalid_type);
                $UPD2['close_memo']         = b_rfid_invalid_type::getName($rfid_invalid_type);
                $this->setRFID($UPD->b_rfid_id,$UPD2,$mod_user);
            }
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 RFID配對
     *
     * @return array
     */
    public function getApiRFIDPairList($rid = 0)
    {
        $ret = array();
        //取第一層
        $data = b_rfid_a::where('b_rfid_id',$rid);
        $data = $data->orderby('isClose')->orderby('id','desc')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['store']       = b_factory::getName($v->b_factory_id);
                $data[$k]['local']       = b_factory_a::getName($v->b_factory_a_id);
                $data[$k]['dept']        = be_dept::getName($v->be_dept_id);
                $data[$k]['b_cust']      = User::getName($v->b_cust_id);
                $data[$k]['car']         = b_car::getNo($v->b_car_id);
                $data[$k]['supply']      = b_supply::getName($v->b_supply_id);
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
