<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_type extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_type';
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
        $data  = e_project_type::where('id',$id);
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
        $data  = e_project_type::find($id);
        return isset($data->name)? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = e_project_type::select('id','name')->where('isClose','N');
        $data = $data->orderby('show_order')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApiSelect($isFirst = 1)
    {
        $ret  = [];
        $data = e_project_type::select('id','name')->where('isClose','N');
        $data = $data->orderby('show_order')->get();
        if($isFirst) $ret[] = ['id'=>0,'name'=>Lang::get('sys_base.base_10015')];
        if(count($data))
        {
            foreach ($data as $key => $val)
            {
                $tmp            = [];
                $tmp['id']      = $val->id;
                $tmp['name']    = $val->name;
                $ret[] = $tmp;
            }
        }

        return $ret;
    }



}
