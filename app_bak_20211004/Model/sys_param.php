<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class sys_param extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 使用者Table:
     */
    protected $table = 'sys_param';
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
        $data  = sys_param::where('id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  回傳系統參數
     * @param $id
     * @return int
     */
    protected function getParam($code,$default = '')
    {
        if(!$code) return $default;
        $data  = sys_param::where('param_code',$code)->select('param_value')->first();
        return (isset($data->param_value))? $data->param_value : $default;
    }
    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function updateParam($code,$val)
    {
        if(!$code || !$val) return false;
        return sys_param::where('param_code',$code)->update(['param_value'=>$val]);
    }

}
