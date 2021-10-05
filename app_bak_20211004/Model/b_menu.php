<?php

namespace App\Model;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_menu extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';
    /**
     * 使用者Table:
     */
    protected $table = 'b_menu';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //選單是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = b_menu::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        return b_menu::find($id)->name;
    }

    //取得 自己最上層
    protected  function getFirstStand($id)
    {
        if(!$id) return false;
        $data = b_menu::find($id);
        $parent_id = (isset($data->parent_id))? $data->parent_id : 0;
        if(!$parent_id) return false;

        do {
            $data = b_menu::find($parent_id);
            $parent_id = (isset($data->parent_id))? $data->parent_id : 0;

        } while ($parent_id);

        return $parent_id;
    }

    //取得 下一級
    protected  function getNextLevelMenu($id,$isSelect = 0)
    {
        if(!$id || $id < 0) $id = 1;
        $ret = array();
        $data =  b_menu::where('parent_id',$id)->get();
        if(count($data))
        {
            if($isSelect)
            {
                foreach ($data as $val)
                {
                    $ret[$val->id] = $val->name;
                }
            } else {
                $ret = $data;
            }
        }
        return $ret;
    }

    //取得 自己所屬的全部
    protected  function getBelongMenu($id)
    {
        if(!$id) return false;
        $ret = array($id);
        $parent_id = b_menu::find($id)->parent_id;

        do {
            $data = b_menu::find($parent_id);
            $parent_id = (isset($data->parent_id))? $data->parent_id : 0;
            if($parent_id)
            {
                $ret[] = $parent_id;
            }
        } while ($parent_id);

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($kind = '',$isFirst = 1,$isTag = 1)
    {
        $ret  = [];
        $menu = b_menu::orderby('parent_id')->orderby('show_order')->select('id','name','parent_id');
        if($kind)
        {
            $menu = $menu->where('kind',$kind);
        }
        $menu = $menu->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10305');
        $menuAry = $ret;

        foreach ($menu as $key => $val)
        {
            $menuAry[$val->id] = $val->name;
        }

        foreach ($menu as $key => $val)
        {
            $parent = '';
            if($isTag)
            {
                $parent = ($val->parent_id && isset($menuAry[$val->parent_id]))? $menuAry[$val->parent_id].'>' : '＊';
            }

            $ret[$val->id] = $parent. $val->name;
        }

        return $ret;
    }
}
