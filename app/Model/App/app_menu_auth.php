<?php

namespace App\Model\App;

use Illuminate\Database\Eloquent\Model;

class app_menu_auth extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'app_menu_auth';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     * 「選單群組」是否存在
     * @param $id
     * @return int
     */
    protected function isExist($gid,$mid = 0)
    {
        if(!$gid) return 0;
        $data  = app_menu_auth::where('app_menu_group_id',$gid)->where('isClose','N');
        if($mid)
        {
            $data = $data->where('app_menu_id',$mid);
        }
        return $data->count();
    }

    /**
     * 取得「選單群組」的權限
     * @param $gid
     * @return array
     */
    protected function getAppAuthMenu($gid)
    {
        if(!$gid) return [];
        $data  = app_menu_auth::where('app_menu_group_id',$gid)->where('isClose','N');
        return $data->get();
    }

    /**
     * 取得「選單群組」的權限+MENU選單
     * @param $gid
     * @return array
     */
    protected function getAppAuthMenuData($gid)
    {
        $menu = [];
        if(!$gid) return $menu;
        $data  = app_menu_auth::join('app_menu','app_menu_auth.app_menu_id','=','app_menu.id')
                ->where('app_menu_auth.app_menu_group_id',$gid)->where('app_menu_auth.isClose','N')->where('app_menu.isClose','N')
                ->select('app_menu_auth.app_menu_group_id','app_menu.*')->orderby('app_menu.parent_id')->orderby('app_menu.show_order');

        if($data->count())
        {
            $data = $data->get();
            $bc_type = app_menu_group::getBcType($gid);
//            dd($bc_type,$data);
            foreach ($data as $val)
            {
                if($val->parent_id == 0)
                {
                    $tmp = [];
                    $tmp['id']    = $val->id;
                    $tmp['name']     = $val->name;
                    $tmp['sub_menu'] = [];
                    $menu[$val->id] = $tmp;
                }
            }
            foreach ($data as $val)
            {
                if(isset($menu[$val->parent_id]))
                {
                    $tmp = [];
                    $tmp['id']    = $val->id;
                    $tmp['name']    = $val->name;
                    $tmp['search']  = app_menu_a::getApiSelect($val->id,$bc_type);
                    $menu[$val->parent_id]['sub_menu'][] = $tmp;
                }
            }
        }

        return $menu;
    }

}
