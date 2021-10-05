<?php

namespace App\Http\Traits;

use App\Model\b_menu_auth;
use App\Model\b_menu_group;
use App\Model\b_menu;
use App\Model\c_menu_auth;
use App\Model\User;
use App\Lib\SHCSLib;
use DB;

/**
 * 選單群組＿權限.
 * User: dorado
 *
 */
trait MenuContractorAuthTrait
{
    /**
     * 新增 選單群組＿權限
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createMenuAuth($gid,$mid,$mod_user = 1)
    {
        $ret = false;
        if(!$gid || !$mid) return $ret;

        $beAuth = new c_menu_auth();
        $beAuth->c_menu_group_id    = $gid;
        $beAuth->c_menu_id          = $mid;
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
    public function setMenuAuth($gid,$menus,$mod_user = 1)
    {
        $ret = false;
        if(!$gid) return $ret;
        $now = date('Y-m-d H:i:s');

        //1. 原本的權限，全部作廢
        DB::table('c_menu_auth')->where('c_menu_group_id', $gid)->update(['isClose' => 'Y','close_user'=>$mod_user,'close_stamp'=>$now]);

        //2. 啟用[本次生效選單]權限
        if(count($menus))
        {
            foreach($menus as $key => $val)
            {
                //2-1. 確認是否存在
                $auth = c_menu_auth::where('c_menu_group_id',$gid)->where('c_menu_id',$val)->first();
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
                    $ret = $this->createMenuAuth($gid,$val,$mod_user);
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
    public function getApiMenuAuthList($gid)
    {
        $ret = array('1'=>1,'2'=>2); //預設 首頁/不顯示 都要有
        //取第一層
        $data = c_menu_auth::getAuthMenu($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $ret[$v->c_menu_id] = $v->c_menu_id;
            }
        }

        return $ret;
    }

    /**
     * 取得 Menu 權限ＲＵＩ
     *
     * @return array
     */
    public function getApiMenuAuthRui($gid)
    {
        $ret = array();
        //取第一層
        $data = c_menu_auth::getAuthMenuData($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $ret[$v->uri] = $v->id;
            }
        }

        return $ret;
    }

}
