<?php

namespace App\Model\View;

use App\Lib\SHCSLib;
use App\Model\Bcust\b_cust_a;
use App\Model\Emp\be_dept;
use App\Model\Engineering\e_project_license;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_supply_etraning extends Model
{
    /**
     * 使用者Table: 承攬商成員允許配卡白名單
     */
    protected $table = 'view_supply_etraning';
    /**
     * Table Index:
     */
    protected $primaryKey = 'b_cust_id';

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
     *  取得 到期日
     * @param $id
     * @return int
     */
    protected function getVailDate($b_cust_id)
    {
        $data =  view_supply_etraning::where('b_cust_id',$b_cust_id)->select('valid_date')->first();
        return isset($data->valid_date)? $data->valid_date : '';
    }

    /**
     *  取得 可配卡之承攬商名單(P01010格式)
     * @param $id
     * @return int
     */
    protected function getSupplySelect($supply_id, $limit_date = '')
    {
        $ret  = [];
        $data =  view_supply_etraning::where('b_supply_id',$supply_id)->select('b_cust_id','name');
        if($limit_date)
        {
            $data = $data->whereRaw('DATEADD(day, -14, valid_date) >= GETDATE()');
        }

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $ret[$val->b_cust_id] = $val->name;
            }
        }

        return $ret;
    }
    /**
     *  取得 可配卡之承攬商名單(P01010格式)
     * @param $id
     * @return int
     */
    protected function getMemberSelect($memberAry = [], $limit_date = '')
    {
        $ret  = [];
        $data =  view_supply_etraning::whereIn('b_cust_id',$memberAry)->select('b_cust_id','name','valid_date');
        if($limit_date)
        {
            $data = $data->whereRaw('DATEADD(day, -14, valid_date) >= GETDATE()');
        }

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $tmp = [];
                $tmp['name']            = $val->name;
                $tmp['valid_date']      = $val->valid_date;
                $ret[$val->b_cust_id]   = $tmp;
            }
        }

        return $ret;
    }

}
