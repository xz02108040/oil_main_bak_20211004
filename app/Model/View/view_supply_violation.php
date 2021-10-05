<?php

namespace App\Model\View;

use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\Supply\b_supply;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_supply_violation extends Model
{
    /**
     * 使用者Table: 列出承攬項目之可進出的廠區
     */
    protected $table = 'view_supply_violation';
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
     *  承攬商 是否在該廠區是否有承攬項目
     * @param $id
     * @return int
     */
    protected function getSelect()
    {
        $ret = $select = [];
        $data  = view_supply_violation::select('name','b_cust_id','violation_record4','limit_edate')->groupby('b_cust_id');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $select[] = $val->b_cust_id;

                $tmp = [];
                $tmp['name']                = $val->name;
                $tmp['b_cust_id']           = $val->b_cust_id;
                $tmp['violation_record4']   = $val->violation_record4;
                $tmp['limit_edate']         = $val->limit_edate;
                $ret[$val->b_cust_id] = $tmp;
            }
        }

        return [$select,$ret];
    }


}
