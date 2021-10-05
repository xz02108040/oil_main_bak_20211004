<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class e_violation extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_violation';
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
        $data  = e_violation::where('id',$id);
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
        $data  = e_violation::find($id);
        return isset($data->name)? $data->name : '';
    }

    /**
     *  取得完整 的資料
     * @param $id
     * @return int
     */
    protected function getData($id)
    {
        if(!$id) return [];
        $data  = e_violation::where('e_violation.id',$id)->where('e_violation.isClose','N')->
                join('e_violation_law as l','e_violation.e_violation_law_id','=','l.id')->
                join('e_violation_punish as p','e_violation.e_violation_punish_id','=','p.id')->
                join('e_violation_type as t','e_violation.e_violation_type_id','=','t.id')->
                select('e_violation.*','l.name as law','p.name as punish','t.name as type');
        return $data->count() ? $data->first() : [];
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = e_violation::select('id','name')->where('isClose','N');
        $data = $data->get();

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
        $data = e_violation::select('id','name')->where('isClose','N');
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $tmp = [];
            $tmp['id'] = $val->id;
            $tmp['name'] = $val->name;
            $ret[] = $tmp;
        }

        return $ret;
    }



}
