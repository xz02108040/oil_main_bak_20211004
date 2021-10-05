<?php

namespace App\Http\Traits\App;

use App\Lib\SHCSLib;
use App\Model\App\app_menu;
use App\Model\App\app_menu_a;
use App\Model\User;

/**
 * APP MENU 搜尋條件
 *
 */
trait AppMenuSelectTrait
{
    /**
     * 新增ＭＥＮＵ
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createAppMenuSelect($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->app_menu_id)) return $ret;

        $vc_menu = new app_menu_a();
        $vc_menu->app_menu_id   = $data->app_menu_id;
        $vc_menu->name          = $data->name;
        $vc_menu->param         = $data->param;
        $vc_menu->type          = isset($vc_menu->type)? $vc_menu->type : 'text';
        $vc_menu->bc_type       = (int)$vc_menu->bc_type;
        $vc_menu->new_user      = $mod_user;
        $vc_menu->mod_user      = $mod_user;

        $ret = ($vc_menu->save())? $vc_menu->id : 0;
        return $ret;
    }

    /**
     * 修改ＭＥＮＵ
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setAppMenuSelect($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $vc_menu = app_menu_a::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $vc_menu->name)
        {
            $isUp++;
            $vc_menu->name = $data->name;
        }
        //對應參數
        if(isset($data->param) && strlen($data->param) && $data->param !== $vc_menu->param)
        {
            $isUp++;
            $vc_menu->param = $data->param;
        }

        //搜尋元件類型
        if(isset($data->type) && strlen($data->type) && $data->type !== $vc_menu->type)
        {
            $isUp++;
            $vc_menu->type = $data->type;
        }
        //帳號類型
        if(isset($data->bc_type) && is_numeric($data->bc_type) && $data->bc_type !== $vc_menu->bc_type)
        {
            $isUp++;
            $vc_menu->bc_type = $data->bc_type;
        }
        //停用
        if(isset($data->isClose) && $data->isClose && $data->isClose !== $vc_menu->isClose)
        {
            $isUp++;
            if($data->isClose == 'Y')
            {
                $vc_menu->isClose = $data->isClose;
                $vc_menu->close_user = $mod_user;
                $vc_menu->close_stamp = $now;
            } else {
                $vc_menu->isClose = 'N';
            }
        }

        if($isUp)
        {
            $vc_menu->mod_user = $mod_user;
            $ret = $vc_menu->save();
        } else {
            $ret = -1;
        }

        return $ret;
    }
    /**
     * 取得 Menu 選單列表
     *
     * @return array
     */
    public function getAppMenuSelectList($app_menu_id)
    {
        $ret = array();
        //取第一層
        $data = app_menu_a::where('app_menu_id',$app_menu_id)->orderby('isClose');

        if($data->count())
        {
            $ret = $data->get();
        }
        return $ret;
    }
    /**
     * 取得 Menu 選單列表 (Api)
     *
     * @return array
     */
    public function getApiAppMenuSelectList($app_menu_id,$bc_type = 2)
    {
        $ret = array();
        //取第一層
        $data = app_menu_a::where('app_menu_id',$app_menu_id)->where('isClose','N');
        if($bc_type)
        {
            $data = $data->where('bc_type',$bc_type);
        }

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $v)
            {
                $tmp = [];
                $tmp['name'] = $v->name;
                $tmp['param'] = $v->param;
                $tmp['type'] = $v->type;
                $ret[] =$tmp;
            }
        }
        return $ret;
    }



}
