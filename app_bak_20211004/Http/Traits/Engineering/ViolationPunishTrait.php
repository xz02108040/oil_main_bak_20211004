<?php

namespace App\Http\Traits\Engineering;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_violation_punish;
use App\Model\User;

/**
 * 違規罰則
 *
 */
trait ViolationPunishTrait
{
    /**
     * 新增 違規罰則
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createViolationPunish($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new e_violation_punish();
        $INS->name          = $data->name;
        $INS->show_order    = $data->show_order ? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 違規罰則
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setViolationPunish($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_violation_punish::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //說明
        if(isset($data->memo) && strlen($data->memo) && $data->memo !==  $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !==  $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
        }
        //是否管制進出
        if(isset($data->isControl) && in_array($data->isControl,['Y','N']) && $data->isControl !==  $UPD->isControl)
        {
            $isUp++;
            $UPD->isControl = $data->isControl;
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
     * 取得 違規罰則
     *
     * @return array
     */
    public function getApiViolationPunishList()
    {
        $ret = array();
        //取第一層
        $data = e_violation_punish::orderby('isClose')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = (object)$data;
        }

        return $ret;
    }

}
