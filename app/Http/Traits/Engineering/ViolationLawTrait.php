<?php

namespace App\Http\Traits\Engineering;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_violation_law;
use App\Model\User;

/**
 * 違規法條
 *
 */
trait ViolationLawTrait
{
    /**
     * 新增 違規法條
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createViolationLaw($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;

        $INS = new e_violation_law();
        $INS->name          = $data->name;
        $INS->law_code      = $data->law_code;
        $INS->memo          = $data->memo;
        $INS->show_order    = $data->show_order ? $data->show_order : 999;

        $INS->new_user      = $mod_user;
        $INS->mod_user      = $mod_user;
        $ret = ($INS->save())? $INS->id : 0;

        return $ret;
    }

    /**
     * 修改 違規法條
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setViolationLaw($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $UPD = e_violation_law::find($id);
        if(!isset($UPD->name)) return $ret;
        //名稱
        if(isset($data->name) && $data->name && $data->name !==  $UPD->name)
        {
            $isUp++;
            $UPD->name = $data->name;
        }
        //代碼
        if(isset($data->law_code) && strlen($data->law_code) && $data->law_code !==  $UPD->law_code)
        {
            $isUp++;
            $UPD->law_code = $data->law_code;
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
     * 取得 違規法條
     *
     * @return array
     */
    public function getApiViolationLawList()
    {
        $ret = array();
        //取第一層
        $data = e_violation_law::orderby('isClose')->orderby('show_order')->get();

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
