<?php

namespace App\Http\Traits\Engineering;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_violation;
use App\Model\Engineering\e_violation_law;
use App\Model\Engineering\e_violation_punish;
use App\Model\Engineering\e_violation_type;
use App\Model\User;

/**
 * 違規事項
 *
 */
trait ViolationTrait
{
    /**
     * 新增 違規事項
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createViolation($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new e_violation();
        $INS->name                  = $data->name;
        $INS->violation_code        = $data->violation_code;
        $INS->e_violation_type_id   = $data->e_violation_type_id;
        $INS->e_violation_law_id    = $data->e_violation_law_id;
        $INS->e_violation_punish_id = $data->e_violation_punish_id;
        $INS->isControl             = $data->isControl == 'Y'? 'Y' : 'N';
        $INS->limit_day             = $data->limit_day > 0 ? $data->limit_day : 0;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 違規事項
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setViolation($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_violation::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //代碼
        if(isset($data->violation_code) && strlen($data->violation_code) && $data->violation_code !==  $UPD->violation_code)
        {
            $isUp++;
            $UPD->violation_code = $data->violation_code;
        }
        //違規分類
        if(isset($data->e_violation_type_id) && is_numeric($data->e_violation_type_id) && $data->e_violation_type_id !==  $UPD->e_violation_type_id)
        {
            $isUp++;
            $UPD->e_violation_type_id = $data->e_violation_type_id;
        }
        //違規法規
        if(isset($data->e_violation_law_id) && is_numeric($data->e_violation_law_id) && $data->e_violation_law_id !==  $UPD->e_violation_law_id)
        {
            $isUp++;
            $UPD->e_violation_law_id = $data->e_violation_law_id;
        }
        //違規罰則
        if(isset($data->e_violation_punish_id) && is_numeric($data->e_violation_punish_id) && $data->e_violation_punish_id !==  $UPD->e_violation_punish_id)
        {
            $isUp++;
            $UPD->e_violation_punish_id = $data->e_violation_punish_id;
        }
        //限制進出
        if(isset($data->isControl) && in_array($data->isControl,['Y','N']) && $data->isControl !==  $UPD->isControl)
        {
            $isUp++;
            $UPD->isControl = $data->isControl;
        }
        //限制天數
        if(isset($data->limit_day) && is_numeric($data->limit_day) && $data->limit_day !==  $UPD->limit_day)
        {
            $isUp++;
            $UPD->limit_day = $data->limit_day;
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
     * 取得 違規事項
     *
     * @return array
     */
    public function getApiViolationList()
    {
        $ret = array();
        $typeAry    = e_violation_type::getSelect();
        $lawAry     = e_violation_law::getSelect();
        $punishAry  = e_violation_punish::getSelect();
        //取第一層
        $data = e_violation::orderby('id')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['violation_type']     = isset($typeAry[$v->e_violation_type_id])? $typeAry[$v->e_violation_type_id] : '';
                $data[$k]['violation_law']      = isset($lawAry[$v->e_violation_law_id])? $lawAry[$v->e_violation_law_id] : '';
                $data[$k]['violation_punish']   = isset($punishAry[$v->e_violation_punish_id])? $punishAry[$v->e_violation_punish_id] : '';
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
