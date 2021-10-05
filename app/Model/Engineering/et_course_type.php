<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class et_course_type extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'et_course_type';
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
        $data  = et_course_type::where('id',$id);
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
        $data  = et_course_type::find($id);
        return isset($data->name)? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = et_course_type::select('id','name')->where('isClose','N');
        $data = $data->orderby('show_order')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }



}
