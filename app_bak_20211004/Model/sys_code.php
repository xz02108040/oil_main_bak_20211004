<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class sys_code extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 使用者Table:
     */
    protected $table = 'sys_code';
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
        $data  = sys_code::where('id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function getCodeName($code)
    {
        if(!$code) return '';
        $data  = sys_code::where('status_code',$code)->first();
        return (isset($data->status_memo))? $data->status_memo : '';
    }
    /**
     *  下拉選擇器
     * @param $id
     * @return int
     */
    protected function getSelect()
    {
        $ret = [];
        $data  = sys_code::all();

        foreach ($data as $val)
        {
            $ret[$val->status_code] = $val->status_memo;
        }

        return $ret;
    }

}
