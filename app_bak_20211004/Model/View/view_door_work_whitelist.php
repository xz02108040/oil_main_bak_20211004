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

class view_door_work_whitelist extends Model
{
    /**
     * 使用者Table: 列出承攬項目之可進出的廠區
     */
    protected $table = 'view_door_work_whitelist';
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
        $data  = view_door_work_whitelist::select('supply','name','b_cust_id','mobile1','head_img_at','head_img','rfid_code','permit_no');
        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if(isset($ret[$val->b_cust_id]))
                {
                    $ret[$val->b_cust_id]['work'][] = $val->permit_no;
                } else {
                    $select[] = $val->b_cust_id;

                    $tmp = [];
                    $tmp['name']        = $val->name;
                    $tmp['supply']      = $val->supply;
                    $tmp['b_cust_id']   = $val->b_cust_id;
                    $tmp['mobile1']     = $val->mobile1;
                    $tmp['head_img_at'] = $val->head_img_at;
                    $tmp['head_img']    = $val->head_img;
                    $tmp['rfid_code']   = $val->rfid_code;
                    $tmp['work'][]      = $val->permit_no;
                    $tmp['err_code']    = 0;
                    $tmp['err_memo']    = '';
                    $ret[$val->b_cust_id] = $tmp;
                }

            }
        }

        return [array_unique($select),$ret];
    }


}
