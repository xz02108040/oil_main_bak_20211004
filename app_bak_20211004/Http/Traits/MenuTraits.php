<?php
namespace App\Http\Traits;

use App\Lib\SHCSLib;
use App\Model\b_menu;
use DB;
use Session;
/**
 *
 */
trait MenuTraits {
    /**
     * 新增ＭＥＮＵ
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createMenu($data,$mod_user = 1)
    {
        $ret = false;
        if(!count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        if(!isset($data->name) || !isset($data->parent_id) || !isset($data->uri)) return $ret;
        $order  = (isset($data->order))? $data->order : 999;
        $target = (isset($data->target))? $data->target : '';
        $icon = (isset($data->icon))? $data->icon : '';
        $isShow = (isset($data->isShow))? $data->isShow : 'Y';


        $vc_menu = new b_menu();
        $vc_menu->name      = $data->name;
        $vc_menu->kind      = $data->kind;
        $vc_menu->parent_id = $data->parent_id;
        $vc_menu->uri       = strlen($data->uri)? $data->uri : '#';
        $vc_menu->func_uri  = strlen($data->func_uri)? $data->func_uri : '';
        $vc_menu->show_order= is_numeric($order)? $order : 999;
        $vc_menu->target    = (in_array($target,['_self','_blank']))? $target : '_self';
        $vc_menu->icon      = $icon;
        $vc_menu->isShow    = $isShow;
        $vc_menu->new_user  = $mod_user;
        $vc_menu->mod_user  = $mod_user;

        $ret = ($vc_menu->save())? $vc_menu->id : 0;
        if($ret)
        {
            //總部選單
            if($vc_menu->kind == 'A')
            {
                //最高權限自動獲得權限
                $this->createMenuAuth(2,$ret,$mod_user);
            }
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
    public function setMenu($id,$data,$mod_user = 1)
    {
        $ret = false;
        if(!$id || !count($data)) return $ret;
        if(is_array($data)) $data = (object)$data;
        $now = date('Y-m-d H:i:s');
        $isUp = 0;

        $vc_menu = b_menu::find($id);
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
        //路由
        if(isset($data->uri) && $data->uri !== $vc_menu->uri)
        {
            $isUp++;
            $vc_menu->uri = $data->uri;
        }
        //相關路由
        if(isset($data->func_uri) && $data->func_uri != $vc_menu->func_uri)
        {
            $isUp++;
            $vc_menu->func_uri = $data->func_uri;
        }
        //icon
        if(isset($data->icon) && $data->icon !== $vc_menu->icon)
        {
            $isUp++;
            $vc_menu->icon = $data->icon;
        }
        //排序
        if(isset($data->order) && is_numeric($data->order) && $data->order !== $vc_menu->show_order)
        {
            $isUp++;
            $vc_menu->show_order = $data->order;
        }
        //目標
        if(isset($data->target) && in_array($data->target,['_self','_blank']) && $data->target !== $vc_menu->target)
        {
            $isUp++;
            $vc_menu->target = $data->target;
        }
        //目標
        if(isset($data->isShow) && in_array($data->isShow,['Y','N']) && $data->isShow !== $vc_menu->isShow)
        {
            $isUp++;
            $vc_menu->isShow = $data->isShow;
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
    public function getApiMenuList($kind = 'X')
    {
        $ret = array();
        //取第一層
        $data = b_menu::where('kind',$kind)->where('isClose','N')->orderby('show_order')->get();

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
                    $ret = $ret + $this->getApiMenuNext($menuAry,$pid);
                }

            }
        }
        return $ret;
    }

    public function getApiMenuNext($menuAry,$pid=0)
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
                    $ret = $ret + $this->getApiMenuNext($menuAry,$pid);
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
    public function getApiMenu($b_menu_group_id, $kind = 'X', $token = '')
    {
        $ret = $wirteAry = array();
        $authAry     = $this->getApiMenuAuthList($b_menu_group_id);
        //取第一層
        $data = b_menu::where('kind',$kind)->where('isClose','N')->where('isShow','Y')->orderby('show_order')->get();

        if(is_object($data))
        {
            $menuAry = array();
            foreach ($data as $v)
            {
                //如果權限允許
                if(isset($authAry[$v->id]))
                {
                    $menuAry[$v->parent_id][] =$v;
                    //讀取權限
                    if(isset($authAry[$v->id]['W']) && $authAry[$v->id]['W'] == 'Y')
                    {
                        $wirteAry[] = $v->id;
                    }
                }
            }
            if(count($menuAry))
            {
                foreach ($menuAry[0] as $v)
                {
                    $pid = $v->id;
                    $uri = '#';
                    if($v->uri)
                    {
                        $uri = str_replace('{t}', $token,$v->uri);
                    }


                    $tmp = array();
                    $tmp['id']      = $pid;
                    $tmp['text']    = $v->name;
                    $tmp['icon']    = $v->icon;
                    $tmp['href']    = $uri;
                    $tmp['target']  = ($v->target)? $v->target : '_self';
                    $tmp['wirte']   = in_array($pid,$wirteAry)? 'Y' : 'N';
                    $tmp['class']   = '';

                    if(isset($menuAry[$pid]))
                    {
                        $tmp['class']         = 'treeview';
                        $tmp['submenu_class'] = 'treeview-menu';
                        $tmp['submenu']       = $this->getLoopMenu($menuAry,$pid,$token);
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
    public function getLoopMenu($menuAry,$pid = 0,$token = '')
    {
        $ret = array();
        if(!$pid || !isset($menuAry[$pid])) return $ret;

        $data = $menuAry[$pid];

        if(count($data))
        {
            foreach ($data as $v)
            {
                $pid = $v->id;

                $uri = '#';
                if($v->uri)
                {
                    $uri = str_replace('{t}', ($token),$v->uri);
                }
                $tmp = array();
                $tmp['id']            = $pid;
                $tmp['text']          = $v->name;
                $tmp['icon']          = $v->icon;
                $tmp['href']          = $uri;
                $tmp['target']        = ($v->target)? $v->target : '_self';
                if(isset($menuAry[$pid]))
                {
                    $tmp['class']         = 'treeview';
                    $tmp['submenu_class'] = 'treeview-menu';
                    $tmp['submenu']       = $this->getLoopMenu($menuAry,$pid,$token);
                }
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

}
