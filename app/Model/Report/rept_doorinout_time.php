<?php

namespace App\Model\Report;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;

class rept_doorinout_time extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'rept_doorinout_time';
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

    public static function getErrCnt($store,$uid,$door_date1,$door_date2 = '')
    {
        if(!$door_date2) $door_date2 = $door_date1;
        return rept_doorinout_time::where('b_factory_id',$store)->where('b_cust_id',$uid)->
                where('door_date1','>=',$door_date1)->where('door_date1','<=',$door_date2)->
                where('result','N')->where('isClose','N')->count();
    }

}
