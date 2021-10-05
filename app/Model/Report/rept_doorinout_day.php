<?php

namespace App\Model\Report;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;

class rept_doorinout_day extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'rept_doorinout_day';
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
     *  複數人員是否存在
     * @param $id
     * @return int
     */
    protected function isExist($store,$date,$b_cust_id)
    {
        if(!$store || !$date || !$b_cust_id) return 0;
        $data  = rept_doorinout_day::where('b_factory_id',$store)->where('door_date',$date);
        $data = $data->where('b_cust_id',$b_cust_id);
        $data = $data->first();
        return isset($data->id)? $data->id : 0;
    }
}
