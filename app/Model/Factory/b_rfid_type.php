<?php

namespace App\Model\Factory;

use App\Model\sys_param;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_rfid_type extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_rfid_type';
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
        $data  = b_rfid_type::where('id',$id)->where('isClose','N');
        return $data->count();
    }

    /**
     *  名稱
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return '';
        $data  = b_rfid_type::find($id);
        return isset($data->name)? $data->name : '';
    }

    //取得 下拉選擇全部
    protected  function getSelect($selectType = 1,$isFirst = 1)
    {
        $ret  = [];
        $data = b_rfid_type::select('id','name')->where('isClose','N');

        if(in_array($selectType,[2,3]))
        {
            $param    = ($selectType == 3)? 'RFID_TYPE_CAR_PARAM' : 'RFID_TYPE_MEN_PARAM';
            $paramAry = sys_param::getParam($param);
            $data = $data->whereIn('id',$paramAry);
        }

        $data = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

}
