<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_door1 extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_door1';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isAliveExist($b_supply_id,$kind,$b_factory_id,$user_id)
    {
        if(!$b_supply_id ||!$kind || !$b_factory_id || !$user_id) return '';
        $typeAry  = SHCSLib::getCode('RP_DOOR_KIND1');
        $today    = date('Y-m-d');

        $data = b_supply_rp_door1::join('b_supply_rp_door1_a as a','a.b_supply_rp_door1_id','=','b_supply_rp_door1.id')->
        where('b_supply_rp_door1.b_supply_id',$b_supply_id)->
        where('b_supply_rp_door1.apply_door_kind',$kind)->
        where('a.isClose','N')->select('b_supply_rp_door1.apply_door_kind','b_supply_rp_door1.sdate',
            'b_supply_rp_door1.edate','b_supply_rp_door1.b_factory_id');
        $data = $data->whereIn('b_supply_rp_door1.aproc',['O']);
        $data = $data->where('b_supply_rp_door1.edate','>=',$today);
        $data = $data->where('b_supply_rp_door1.b_factory_id',$b_factory_id);

        if($kind == 2)
        {
            $data = $data->where('a.b_car_id',$user_id);
        } else {
            $data = $data->where('a.b_cust_id',$user_id);
        }
        $data = $data->first();
        if(isset($data->sdate))
        {
            $kindName   = isset($typeAry['apply_door_kind'])? $typeAry['apply_door_kind'] : '';
            $applyRange = ($data->sdate != $data->edate)? ($data->sdate.' - '.$data->edate) : $data->sdate;
            $allowStr   = \Lang::get('sys_supply.supply_124',['sdate'=>$applyRange]);
            return $kindName.'('.$allowStr.')';
        } else {
            return '';
        }
    }
}
