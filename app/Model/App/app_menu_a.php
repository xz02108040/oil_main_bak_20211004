<?php

namespace App\Model\App;

use Illuminate\Database\Eloquent\Model;
use Lang;

class app_menu_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'app_menu_a';
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
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = app_menu_a::where('id',$id);
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return 0;
        $data  = app_menu_a::find($id);
        return isset($data->name)? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = app_menu_a::select('id','name')->where('isClose','N');
        $data = $data->orderby('show_order')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($fid,$bc_type = 0)
    {
        $ret  = [];
        $data = app_menu_a::where('app_menu_id',$fid)->where('isClose','N')->whereIn('bc_type',[1,$bc_type]);
        $data = $data->get();

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['index']   = $val->param;
            $tmp['name']    = $val->name;
            $tmp['type']    = $val->type;
            $ret[]  = $tmp;
        }

        return $ret;
    }



}
