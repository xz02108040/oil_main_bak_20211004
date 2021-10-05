<?php

namespace App\Http\Traits;

use App\Model\b_cust_a;
use App\Model\b_cust_p;
use App\Model\b_menu_group;
use App\Model\c_menu_group;
use App\Model\User;
use App\Lib\SHCSLib;
use App\Model\v_cust;
use App\Model\v_stand;
use App\Model\b_menu;

/**
 * Menu.
 * User: dorado
 * Date: 2017/8/20
 *
 */
trait MenuContractorGroupTrait
{
    /**
     * 新增 權限群組
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createMenuGroup($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name)) return $ret;
        $order  = (isset($data->order))? $data->order : 999;

        $b_menu_group = new c_menu_group();
        $b_menu_group->name       = $data->name;
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
    public function setMenuGroup($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $b_menu_group = c_menu_group::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $b_menu_group->name)
        {
            $isUp++;
            $b_menu_group->name = $data->name;
        }
        //排序
        if(isset($data->order) && is_numeric($data->order) && $data->order !== $b_menu_group->show_order)
        {
            $isUp++;
            $b_menu_group->show_order = $data->order;
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
    public function getApiMenuGroupList()
    {
        $ret = array();
        //取第一層
        $data = c_menu_group::orderby('isClose')->orderby('show_order')->get();

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
