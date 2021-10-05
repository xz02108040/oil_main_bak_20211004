<?php

namespace App\Http\Traits\App;

use App\Model\app\app_menu_auth;
use App\Model\app\app_menu_group;
use App\Model\b_cust_a;
use App\Model\b_cust_p;
use App\Model\User;
use App\Lib\SHCSLib;

/**
 * AppMenu.
 * User: dorado
 * Date: 2020/10/14
 *
 */
trait AppMenuGroupTrait
{
    /**
     * 新增 權限群組
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createAppMenuGroup($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
        $order  = (isset($data->show_order))? $data->show_order : 999;

        $b_menu_group = new app_menu_group();
        $b_menu_group->name       = $data->name;
        $b_menu_group->bc_type    = is_numeric($data->bc_type)? $data->bc_type : 2;
        $b_menu_group->show_order = is_numeric($order)? $order : 999;
        $b_menu_group->new_user   = $mod_user;
        $b_menu_group->mod_user   = $mod_user;
        $ret = ($b_menu_group->save())? $b_menu_group->id : 0;

        return $ret;
    }

    /**
     * 修改 權限群組
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setAppMenuGroup($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $b_menu_group = app_menu_group::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $b_menu_group->name)
        {
            $isUp++;
            $b_menu_group->name = $data->name;
        }
        //類型
        if(isset($data->bc_type) && $data->bc_type && $data->bc_type !== $b_menu_group->bc_type)
        {
            $isUp++;
            $b_menu_group->bc_type = $data->bc_type;
        }
        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $b_menu_group->show_order)
        {
            $isUp++;
            $b_menu_group->show_order = $data->show_order;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $b_menu_group->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $b_menu_group->isClose = $data->isClose;
                $b_menu_group->close_user = $mod_user;
                $b_menu_group->close_stamp = $now;
            } else {
                $b_menu_group->isClose = 'N';
            }
        }
        if($isUp)
        {
            $b_menu_group->mod_user = $mod_user;
            $ret = $b_menu_group->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }

    /**
     * 取得 Menu 選單
     *
     * @return array
     */
    public function getApiAppMenuGroupList($bc_type = 2)
    {
        $ret = array();
        //取第一層
        $data = app_menu_group::where('bc_type',$bc_type)->orderby('isClose')->orderby('show_order')->get();

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
