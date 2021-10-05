<?php

namespace App\Model\Engineering;

use Illuminate\Database\Eloquent\Model;
use Lang;

class e_violation_complain extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_violation_complain';
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
     *  是否存在[工程案件]
     * @param $id
     * @return int
     */
    protected function isProjectExist($id)
    {
        if(!$id) return 0;
        $data  = e_violation_complain::where('e_project_id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在[承攬商]
     * @param $id
     * @return int
     */
    protected function isSupplyExist($id)
    {
        if(!$id) return 0;
        $data  = e_violation_complain::where('b_supply_id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在[申訴單]
     * @param $id
     * @return int
     */
    protected function isExist($id)
    {
        if(!$id) return 0;
        $data  = e_violation_complain::where('e_violation_contractor_id',$id)->where('isClose','N');
        //$data  = $data->where('aproc','!=','C');
        return $data->count();
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1)
    {
        $ret  = [];
        $data = e_violation_complain::select('id','name')->where('isClose','N');
        $data = $data->orderby('show_order')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }



}
