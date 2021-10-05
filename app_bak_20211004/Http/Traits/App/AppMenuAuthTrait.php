<?php

namespace App\Http\Traits\App;

use App\Model\app\app_menu_auth;
use App\Model\app\b_menu_auth;
use App\Lib\SHCSLib;
use DB;

/**
 * 選單群組＿權限.
 * User: dorado
 *
 */
trait AppMenuAuthTrait
{
    /**
     * 新增 選單群組＿權限
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createAppMenuAuth($gid,$mid,$mod_user = 1)
    {
        $ret = false;
        if(!$gid || !$mid) return $ret;

        $beAuth = new app_menu_auth();
        $beAuth->app_menu_group_id    = $gid;
        $beAuth->app_menu_id          = $mid;
        $beAuth->new_user           = $mod_user;
        $beAuth->mod_user           = $mod_user;
        $ret = ($beAuth->save())? $beAuth->id : 0;

        return $ret;
    }

    /**
     * 修改 選單群組＿權限
     * @param $id
     * @param $menus 本次生效選單
     * @param int $mod_user
     * @return bool
     */
    public function setAppMenuAuth($gid,$menus,$mod_user = 1)
    {
        $ret = false;
        if(!$gid) return $ret;
        $now = date('Y-m-d H:i:s');

        //1. 原本的權限，全部作廢
        DB::table('app_menu_auth')->where('app_menu_group_id', $gid)->update(['isClose' => 'Y','close_user'=>$mod_user,'close_stamp'=>$now]);

        //2. 啟用[本次生效選單]權限
        if(count($menus))
        {
            foreach($menus as $key => $val)
            {
                //2-1. 確認是否存在
                $auth = app_menu_auth::where('app_menu_group_id',$gid)->where('app_menu_id',$val)->first();
                if(isset($auth->id))
                {
                    //2-1-1. 已存在，重新啟用
                    if($auth->isClose == 'Y')
                    {
                        $auth->isClose = 'N';
                        $auth->mod_user = $mod_user;
                        $ret = $auth->save();
                    }
                } else {
                    //2-2.若是不存在，則 新增
                    $ret = $this->createAppMenuAuth($gid,$val,$mod_user);
                }
            }
        }

        return $ret;
    }

    /**
     * 取得 選單群組＿權限
     *
     * @return array
     */
    public function getApiAppMenuAuthList($gid)
    {
        $ret = array();
        //取第一層
        $data = app_menu_auth::getAppAuthMenu($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $ret[$v->app_menu_id] = $v->app_menu_id;
            }
        }

        return $ret;
    }


}
