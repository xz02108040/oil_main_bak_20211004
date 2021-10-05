<?php

namespace App\Model;

use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use Lang;

class bc_type_app extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 使用者Table:
     */
    protected $table = 'bc_type_app';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //選單是否存在
    protected  function isExist($id)
    {
        if(!$id) return 0;
        $data = bc_type_app::find($id);
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        return bc_type_app::find($id)->name;
    }

    //取得 下拉選擇全部
    protected  function getSelect($bctype,$isFirst = 1)
    {
        $ret  = [];
        $data = bc_type_app::select('id','name');
        if($bctype)
        {
            $data = $data->where('bc_type',$bctype);
        }
        $data = $data->where('isClose','N')->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
