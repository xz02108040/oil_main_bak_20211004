<?php

namespace App\Model\Report;

use Illuminate\Database\Eloquent\Model;

class rept_doorinout_car_t extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'rept_doorinout_car_t';
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
    protected function isExist($store,$date,$b_car_id = [],$door_type = -1,$extCar = [])
    {
        if(!$date) return 0;
        $data  = rept_doorinout_car_t::where('b_factory_id',$store)->where('door_date',$date);
        if(is_array($b_car_id) && count($b_car_id))
        {
            $data = $data->whereIn('b_car_id',$b_car_id);
        }
        if(is_array($extCar) && count($extCar))
        {
            $data = $data->whereNotIn('b_car_id',$extCar);
        }
        if($door_type >= 0)
        {
            $data = $data->where('door_type',$door_type);
        }
        $data = $data->first();
        return isset($data->id)? 1 : 0;
    }

    /**
     *  特定人員在指定日期存在紀錄
     * @param $id
     * @return int
     */
    protected function isDateExist($date,$b_factory_id,$b_factory_d_id = 0,$b_car_id)
    {
        if(!$date && !$b_car_id) return 0;
        $data  = rept_doorinout_car_t::where('b_factory_id',$b_factory_id)->where('b_car_id',$b_car_id)->where('door_date',$date);
        if($b_factory_d_id)
        {
            $data = $data->where('b_factory_d_id',$b_factory_d_id);
        }
        $data = $data->first();
        return isset($data->id)? $data->id : 0;
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getCarCount($date,$store = 0,$door = 0,$supply = 0,$door_type = [1,3])
    {
        if(!$date) return 0;
        $data  = rept_doorinout_car_t::where('door_date',$date)->whereIn('door_type',$door_type);
        if(is_array($store) && count($store))
        {
            $data  = $data->whereIn('b_factory_id',$store);
        }elseif(is_numeric($store))
        {
            $data  = $data->where('b_factory_id',$store);
        }
        if($door)
        {
            $data  = $data->where('b_factory_d_id',$door);
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        return $data->count();
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getTodayCar($store_id,$supply_id,$retType = 1)
    {
        $ret = [];
        if(!$store_id || !$supply_id) return $ret;
        $data  = rept_doorinout_car_t::where('door_date',date('Y-m-d'));
        $data  = $data->where('b_factory_id',$store_id);
        $data  = $data->where('b_supply_id',$supply_id);
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[] = $val->car_no;
            }
        }
        if($retType == 2)
        {
            $ret = implode(',',$ret);
        }

        return $ret;
    }

}
