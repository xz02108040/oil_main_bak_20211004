<?php

namespace App\Model\View;

use App\Model\Emp\be_dept;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_dept_member extends Model
{
    /**
     * 使用者Table: 列出 目前進行中工程之承攬商成員
     */
    protected $table = 'view_dept_member';
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

    protected $guarded = ['b_cust_id'];

    protected function getData($uid)
    {
        if(!$uid) return 0;
        $data = view_dept_member::where('b_cust_id',$uid)->first();
        return $data;
    }

    protected function getStore($uid)
    {
        if(!$uid) return 0;
        $data = view_dept_member::where('b_cust_id',$uid)->select('b_factory_id')->first();
        return isset($data->b_factory_id)? $data->b_factory_id : 0;
    }

    protected function getTitle($uid)
    {
        if(!$uid) return 0;
        $data = view_dept_member::where('b_cust_id',$uid)->select('be_title_id')->first();
        return isset($data->be_title_id)? $data->be_title_id : 0;
    }

    protected function getDept($uid ,$type = 1)
    {
        if(!$uid) return 0;
        $data = view_dept_member::where('b_cust_id',$uid)->select('dept','be_dept_id')->first();
        return isset($data->be_dept_id)? ($type == 2 ? $data->dept : $data->be_dept_id ) : 0;
    }

    protected function getEmpSelect($store = 0, $dept = 0, $titleAry = [])
    {
        $ret = [];
        if(!$store && !$dept) return $ret;
        $data = view_dept_member::where(function ($query) use ($store,$dept) {
            $query->where('b_factory_id', '=', $store)
                ->orWhere('be_dept_id', '=', $dept);
        });
        if(count($titleAry))
        {
            $data = $data->whereIn('be_title_id',$titleAry);
        }
        if($data->count())
        {
            foreach ($data->get() as $key => $val)
            {
                $ret[$val->b_cust_id] = $val->name;
            }
        }

        return $ret;
    }

}
