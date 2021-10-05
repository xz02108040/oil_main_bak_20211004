<?php

namespace App\Http\Traits;

use App\Model\b_cust_a;
use App\Model\b_cust_p;
use App\Model\b_menu_group;
use App\Model\sys_code;
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
trait SysCodeTrait
{
    /**
     * 新增 系統參數
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createSysCode($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->status_code)) return $ret;
        $order  = (isset($data->show_order))? $data->show_order : 999;

        $UPD = new sys_code();
        $UPD->status_code   = $data->status_code;
        $UPD->status_memo   = $data->status_memo;
        $UPD->status_key    = $data->status_key;
        $UPD->status_val    = $data->status_val;
        $UPD->show_order    = is_numeric($order)? $order : 999;
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
    public function setSysCode($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;

        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;
        $UPD = sys_code::find($id);
        //名稱
        if(isset($data->status_code) && $data->status_code && $data->status_code !== $UPD->status_code)
        {
            $isUp++;
            $UPD->status_code = $data->status_code;
        }
        if(isset($data->status_memo) && $data->status_memo && $data->status_memo !== $UPD->status_memo)
        {
            $isUp++;
            $UPD->status_memo = $data->status_memo;
        }
        if(isset($data->status_key) && $data->status_key && $data->status_key !== $UPD->status_key)
        {
            $isUp++;
            $UPD->status_key = $data->status_key;
        }
        if(isset($data->status_val) && $data->status_val && $data->status_val !== $UPD->status_val)
        {
            $isUp++;
            $UPD->status_val = $data->status_val;
        }
        if(isset($data->memo) && $data->memo && $data->memo !== $UPD->memo)
        {
            $isUp++;
            $UPD->memo = $data->memo;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $UPD->show_order)
        {
            $isUp++;
            $UPD->show_order = $data->show_order;
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
        $data = sys_code::where('isClose','N')->orderby('show_order')->get();

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
