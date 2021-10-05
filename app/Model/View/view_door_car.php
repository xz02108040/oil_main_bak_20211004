<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project;
use App\Model\Engineering\e_project_s;
use App\Model\Supply\b_supply_member;
use App\Model\Supply\b_supply_member_ei;
use App\Model\Factory\b_car_type;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_door_car extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商車輛
     */
    protected $table = 'view_door_car';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_car_id';

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

    protected $guarded = ['b_car_id'];

    protected function isExist($supply_id,$car_no)
    {
        if(!$supply_id || !$car_no) return 0;
        $data = view_door_car::where('b_supply_id',$supply_id)->where('car_no',$car_no)->
        select('e_project_id')->first();
        return isset($data->e_project_id)? $data->e_project_id : 0;
    }

    //取得 下拉選擇全部
    protected  function getDoorDateRange($project_id , $b_car_id)
    {
        $data   = view_door_car::select('b_car_id as id','car_no','supply','bc.name as car_type','door_sdate','door_edate')
            ->join('b_car_type as bc', 'bc.id', '=', 'view_door_car.car_type')
            ->where('e_project_id',$project_id)
            ->where('b_car_id',$b_car_id);
        $data   = $data->first();
        if(isset($data->id))
        {
            $ret = $data->car_type.'，'.\Lang::get('sys_supply.supply_111',['sdate'=>($data->door_sdate.' - '.$data->door_edate)]);
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($supply_id , $isShow = 0, $isFisrt = 1)
    {
        $ret    = [];
        $data   = view_door_car::where('b_supply_id',$supply_id)->
        select('b_car_id as id','car_no','supply','car_type','door_sdate','door_edate');
        $data   = $data->get();
        if($isFisrt) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $val)
        {
            $showMemo = '';

            if($isShow)    $showMemo .=  $val->car_type.'，'.\Lang::get('sys_supply.supply_111',['sdate'=>($val->door_sdate.' - '.$val->door_edate)]);
            $ret[$val->id] = $val->car_no. ($isShow? '  ('.$showMemo.')' : '');
        }

        return $ret;
    }

    //取得 車輛選擇 依照工程案件
    protected  function getSelectByProject($supply_id , $isShow = 0, $isFisrt = 1, $project_id = 0)
    {
        $ret    = [];
        $data   = view_door_car::where('b_supply_id',$supply_id)->where('e_project_id',$project_id)->
        select('b_car_id as id','car_no','supply','car_type','door_sdate','door_edate');
        $data   = $data->get();
        if($isFisrt) $ret[0] = Lang::get('sys_base.base_10015');

        $car_type_ary = b_car_type::where('isClose','N')->pluck('name','id');

        foreach ($data as $val)
        {
            $showMemo = '';

            $car_type_name = isset($car_type_ary[$val->car_type]) ? $car_type_ary[$val->car_type] : '';
            if($isShow)    $showMemo .= $car_type_name.'，'.\Lang::get('sys_supply.supply_111',['sdate'=>($val->door_sdate.' - '.$val->door_edate)]);
            $ret[$val->id] = $val->car_no. ($isShow? '  ('.$showMemo.')' : '');
        }

        return $ret;
    }
}
