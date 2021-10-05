<?php

namespace App\Model\App;

use Illuminate\Database\Eloquent\Model;

class app_menu_group extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'app_menu_group';
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
     *  「權限群組」是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = app_menu_group::where('id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  「權限群組名稱」是否存在
     * @param $id
     * @return int
     */
    protected function isNameExist($name,$extid)
    {
        if(!$name) return 0;
        $data  = app_menu_group::where('name',$name)->where('id','!=',$extid)->where('isClose','N');
        return $data->count();
    }
    /**
     *  取得「權限群組名稱」
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = app_menu_group::find($id);
        return (isset($data->name))? $data->name : '';
    }
    /**
     *  取得「權限群組類別」
     * @param $id
     * @return int
     */
    protected function getBcType($id)
    {
        if(!$id) return 0;
        $data  = app_menu_group::find($id);
        return (isset($data->bc_type))? $data->bc_type : 0;
    }

    /**
     * 產生下拉選單「權限群組」
     * @param int $isRoot
     * @return array
     */
    protected function getSelect($excludeAry = [])
    {
        $ret = array();
        $data  = app_menu_group::where('isClose','N')->orderby('show_order')->get();
        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                //排除不顯示的群組
                if(!in_array($val->id,$excludeAry))
                {
                    $ret[$val->id] = $val->name;
                }
            }
        }
        return $ret;
    }
}
