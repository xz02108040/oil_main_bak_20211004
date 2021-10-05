<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_door1_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_door1_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function getAmt($b_supply_rp_door1_id)
    {
        if(!$b_supply_rp_door1_id) return 0;
        $data = b_supply_rp_door1_a::where('b_supply_rp_door1_id',$b_supply_rp_door1_id)->where('isClose','N');

        return $data->count();
    }
}
