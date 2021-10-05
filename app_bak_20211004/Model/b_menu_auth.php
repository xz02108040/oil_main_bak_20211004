<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class b_menu_auth extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 使用者Table:
     */
    protected $table = 'b_menu_auth';
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
        $data  = b_menu_auth::where('b_menu_group_id',$gid)->where('isClose','N');
        if($mid)
        {
            $data = $data->where('b_menu_id',$mid);
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
        $data  = b_menu_auth::where('b_menu_group_id',$gid)->where('isClose','N');
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
        $data  = b_menu_auth::join('b_menu','b_menu_auth.b_menu_id','=','b_menu.id')
                ->where('b_menu_auth.b_menu_group_id',$gid)->where('b_menu_auth.isClose','N')->where('b_menu.isClose','N')
                ->select('b_menu_auth.b_menu_group_id','b_menu_auth.isWrite','b_menu.*')->get();
        return $data;
    }

}
