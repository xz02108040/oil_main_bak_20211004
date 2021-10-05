<?php

namespace App\Http\Traits;

use App\Model\b_cust_a;
use App\Model\b_cust_p;
use App\Model\b_menu_group;
use App\Model\sys_code;
use App\Model\sys_param;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\v_cust;
use App\Model\v_stand;

/**
 * 系統參數.
 * User: dorado
 * Date: 2017/8/20
 *
 */
trait SysParam
{
    /**
     * 新增 系統參數
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSysParam($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->status_code)) return $ret;
        $order  = (isset($data->show_order))? $data->show_order : 999;

        $UPD = new sys_param();
        $UPD->param_name    = $data->param_name;
        $UPD->param_code    = $data->param_code;
        $UPD->param_value   = $data->param_value;
        $UPD->memo          = $data->memo;
        $UPD->new_user      = $mod_user;
        $UPD->mod_user      = $mod_user;
        $ret = $UPD->save()? $UPD->id : 0;
        return $ret;
    }

    /**
     * 修改 系統參數
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setSysParam($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;

        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = sys_param::find($id);
        //名稱
        if(isset($data->param_name) && $data->param_name && $data->param_name !== $UPD->param_name)
        {
            $isUp++;
            $UPD->param_name = $data->param_name;
        }
        if(isset($data->param_code) && $data->param_code && $data->param_code !== $UPD->param_code)
        {
            $isUp++;
            $UPD->param_code = $data->param_code;
        }
        if(isset($data->param_value) && strlen($data->param_value) && $data->param_value !== $UPD->param_value)
        {
            $isUp++;
            $UPD->param_value = $data->param_value;
        }
        if(isset($data->memo) && $data->memo && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $UPD->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $UPD->isClose = $data->isClose;
                $UPD->close_user = $mod_user;
                $UPD->close_stamp = $now;
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
     * 取得 SysCode 選單
     *
     * @return array
     */
    public function getApiSysCodeList()
    {
        $ret = array();
        //取第一層
        $data = sys_param::where('isClose','N')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $data[$k]['close_user']  = User::getName($v->close_user);
                $data[$k]['new_user']    = User::getName($v->new_user);
                $data[$k]['mod_user']    = User::getName($v->mod_user);
            }
            $ret = $data;
        }

        return $ret;
    }

}
