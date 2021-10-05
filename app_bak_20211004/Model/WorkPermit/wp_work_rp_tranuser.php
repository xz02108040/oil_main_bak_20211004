<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_rp_tranuser extends Model
{
    /**
     * Table: 工單延長申請單
     */
    protected $table = 'wp_work_rp_tranuser';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id, $aproc = 'A')
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_rp_tranuser::where('wp_work_id',$wp_work_id)->
            select('id')->where('isClose','N');
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }

        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //是否存在
    protected  function getChargeInfo($id)
    {
        if(!$id) return [0,'',0,0];
        $data = wp_work_rp_tranuser::where('id',$id)->select('wp_work_id','aproc','charge_dept1')->where('isClose','N');

        $data = $data->first();
        $wp_work_id     = (isset($data->wp_work_id))? $data->wp_work_id : 0;
        $aproc          = (isset($data->aproc))? $data->aproc : '';
        $charge_dept1   = (isset($data->charge_dept1))? $data->charge_dept1 : 0;
        $charge_dept2   = (isset($data->charge_dept2))? $data->charge_dept2 : 0;
        return [$wp_work_id,$aproc,$charge_dept1,$charge_dept2];
    }
}
