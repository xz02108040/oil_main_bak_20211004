<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class c_menu_auth extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 使用者Table:
     */
    protected $table = 'c_menu_auth';
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
        $data  = c_menu_auth::where('c_menu_group_id',$gid)->where('isClose','N');
        if($mid)
        {
            $data = $data->where('c_menu_id',$mid);
        }
        return $data->count();
    }

    /**
     * 取得「選單群組」的權限
     * @param $gid
     * @return array
     */
    protected function getAuthMenu($gid)
    {
        if(!$gid) return [];
        $data  = c_menu_auth::where('c_menu_group_id',$gid)->where('isClose','N');
        return $data->get();
    }

    /**
     * 取得「選單群組」的權限+MENU選單
     * @param $gid
     * @return array
     */
    protected function getAuthMenuData($gid)
    {
        if(!$gid) return [];
        $data  = c_menu_auth::join('c_menu','c_menu_auth.c_menu_id','=','c_menu.id')
                ->where('c_menu_auth.c_menu_group_id',$gid)->where('c_menu_auth.isClose','N')->where('c_menu.isClose','N')
                ->select('c_menu_auth.c_menu_group_id','c_menu.*')->get();
        return $data;
    }

}
