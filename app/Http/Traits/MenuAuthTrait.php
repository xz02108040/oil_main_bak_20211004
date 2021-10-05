<?php

namespace App\Http\Traits;

use App\Model\b_menu_auth;
use App\Model\b_menu_group;
use App\Model\b_menu;
use App\Model\User;
use App\Lib\SHCSLib;
use DB;

/**
 * 選單群組＿權限.
 * User: dorado
 *
 */
trait MenuAuthTrait
{
    /**
     * 新增 選單群組＿權限
     * @param $data
     * @param int $mod_user
     * @return bool
     */
    public function createMenuAuth($gid,$mid,$isWrite = 'N',$mod_user = 1)
    {
        $ret = false;
        if(!$gid || !$mid) return $ret;

        $beAuth = new b_menu_auth();
        $beAuth->b_menu_group_id    = $gid;
        $beAuth->b_menu_id          = $mid;
        $beAuth->isWrite            = in_array($isWrite,['Y','N'])? $isWrite : 'N';
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
    public function setMenuAuth($gid,$menusR ,$menusW,$mod_user = 1)
    {
        $ret = false;
        if(!$gid) return $ret;
        $now = date('Y-m-d H:i:s');
        $isWirte = 'N';
        if(!is_array($menusW)) $menusW = [];

        //1. 原本的權限，全部作廢
        DB::table('b_menu_auth')->where('b_menu_group_id', $gid)->update(['isClose' => 'Y','close_user'=>$mod_user,'close_stamp'=>$now]);

        //2. 啟用[本次生效選單]權限
        if(count($menusR))
        {
            //2.1 先處理 讀權限
            foreach($menusR as $key => $menu_id)
            {
                //2-1. 確認是否存在
                $auth = b_menu_auth::where('b_menu_group_id',$gid)->where('b_menu_id',$menu_id)->first();
                if(isset($auth->id))
                {
                    $isWirte = in_array($menu_id,$menusW)? 'Y' : 'N';
                    //2-1-1. 已存在，重新啟用
                    if($auth->isClose == 'Y')
                    {
                        $auth->isWrite = $isWirte;
                        $auth->isClose = 'N';
                        $auth->mod_user = $mod_user;
                        $ret = $auth->save();
                    }
                } else {
                    //2-2.若是不存在，則 新增
                    $ret = $this->createMenuAuth($gid,$menu_id,$isWirte,$mod_user);
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
        $data = b_menu_auth::getAuthMenu($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                $ret[$v->b_menu_id] = ['R'=>'Y','W'=>$v->isWrite];
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
        $data = b_menu_auth::getAuthMenuData($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                //uri
                $uriAry = explode('?',$v->uri);
                $ret[$uriAry[0]] = $v->id;
                //func_uri
                if(isset($v->func_uri) && $v->func_uri)
                {
                    $funcAry = explode(',',$v->func_uri);
                    if(count($funcAry))
                    {
                        foreach ($funcAry as $val)
                        {
                            $uriAry = explode('?',$val);
                            $ret[$uriAry[0]] = $v->id;
                        }
                    }
                }
            }
        }

        return $ret;
    }
    /**
     * 取得 Menu 權限_維護修改
     *
     * @return array
     */
    public function getApiMenuAuthWrite($gid)
    {
        $ret = array();
        //取第一層
        $data = b_menu_auth::getAuthMenuData($gid);
        if(is_object($data))
        {
            foreach ($data as $k => $v)
            {
                //isWrite
                if(isset($v->isWrite) && $v->isWrite == 'Y')
                {
                    $ret[$v->uri] = $v->id;
                    //func_uri
                    if(isset($v->func_uri) && $v->func_uri)
                    {
                        $funcAry = explode(',',$v->func_uri);
                        if(count($funcAry))
                        {
                            foreach ($funcAry as $val)
                            {
                                $ret[$val] = $v->id;
                            }
                        }
                    }
                }
            }
        }

        return $ret;
    }


}
