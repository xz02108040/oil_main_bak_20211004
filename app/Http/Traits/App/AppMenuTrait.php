<?php

namespace App\Http\Traits\App;

use App\Lib\SHCSLib;
use App\Model\App\app_menu;
use App\Model\User;

/**
 * 組織職員
 *
 */
trait AppMenuTrait
{
    /**
     * 新增ＭＥＮＵ
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createAppMenu($data,$mod_user = 1)
    {
        $ret = false;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name) || !isset($data->parent_id)) return $ret;
        $order  = (isset($data->order))? $data->order : 999;

        $vc_menu = new app_menu();
        $vc_menu->name      = $data->name;
        $vc_menu->parent_id = $data->parent_id;
        $vc_menu->show_order= is_numeric($order)? $order : 999;
        $vc_menu->new_user  = $mod_user;
        $vc_menu->mod_user  = $mod_user;

        $ret = ($vc_menu->save())? $vc_menu->id : 0;
        if($ret)
        {

        }
        return $ret;
    }

    /**
     * 修改ＭＥＮＵ
     * @param $id
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function setAppMenu($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $vc_menu = app_menu::find($id);
        //名稱
        if(isset($data->name) && $data->name && $data->name !== $vc_menu->name)
        {
            $isUp++;
            $vc_menu->name = $data->name;
        }
        //父層
        if(isset($data->parent_id) && is_numeric($data->parent_id) && $data->parent_id !== $vc_menu->parent_id)
        {
            $isUp++;
            $vc_menu->parent_id = $data->parent_id;
        }

        //排序
        if(isset($data->show_order) && is_numeric($data->show_order) && $data->show_order !== $vc_menu->show_order)
        {
            $isUp++;
            $vc_menu->show_order = $data->show_order;
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
    public function getApiAppMenuList()
    {
        $ret = array();
        //取第一層
        $data = app_menu::where('isClose','N')->orderby('show_order')->get();

        if(is_object($data))
        {
            foreach ($data as $v)
            {
                $menuAry[$v->parent_id][] =$v;
            }

            foreach ($menuAry[0] as $v)
            {
                $pid = $v->id;
                $ret[$pid] = $v;

                if(isset($menuAry[$pid]))
                {
                    $ret = $ret + $this->getApiAppMenuNext($menuAry,$pid);
                }

            }
        }
        return $ret;
    }

    public function getApiAppMenuNext($menuAry,$pid=0)
    {
        $ret = array();
        if(!$pid || !isset($menuAry[$pid])) return $ret;

        $data = $menuAry[$pid];

        if(count($data))
        {
            foreach ($data as $v)
            {
                $pid = $v->id;
                $ret[$pid] = $v;
                if(isset($menuAry[$pid]))
                {
                    $ret = $ret + $this->getApiAppMenuNext($menuAry,$pid);
                }
            }
        }
        return $ret;
    }

    /**
     * 取得 個人 Menu 選單
     *
     * @return array
     */
    public function getApiAppMenu($app_menu_group_id)
    {
        $ret = array();
        $authAry     = $this->getApiAppMenuAuthList($app_menu_group_id);
        //取第一層
        $data = app_menu::where('isClose','N')->where('isShow','Y')->orderby('show_order')->get();

        if(is_object($data))
        {
            $menuAry = array();
            foreach ($data as $v)
            {
                //如果權限允許
                if(isset($authAry[$v->id]))
                {
                    $menuAry[$v->parent_id][] =$v;
                }
            }
            if(count($menuAry))
            {
                foreach ($menuAry[0] as $v)
                {
                    $pid = $v->id;
                    $tmp = array();
                    $tmp['id']      = $pid;
                    $tmp['text']    = $v->name;

                    if(isset($menuAry[$pid]))
                    {
                        $tmp['submenu']       = $this->getLoopAppMenu($menuAry,$pid);
                    }
                    $ret[] = $tmp;
                }
            }
        }

        return $ret;
    }

    /**
     * @param 取得 下一層ＭＥＮＵ
     * @param int $pid
     * @return array
     */
    public function getLoopAppMenu($menuAry,$pid = 0)
    {
        $ret = array();
        if(!$pid || !isset($menuAry[$pid])) return $ret;

        $data = $menuAry[$pid];

        if(count($data))
        {
            foreach ($data as $v)
            {
                $pid = $v->id;
                $tmp = array();
                $tmp['id']            = $pid;
                $tmp['text']          = $v->name;
                if(isset($menuAry[$pid]))
                {
                    $tmp['submenu']       = $this->getLoopAppMenu($menuAry,$pid);
                }
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

}
