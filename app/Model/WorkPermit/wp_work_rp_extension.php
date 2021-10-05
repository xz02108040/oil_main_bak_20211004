<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_rp_extension extends Model
{
    /**
     * Table: 工單延長申請單
     */
    protected $table = 'wp_work_rp_extension';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id, $isPass = 'N')
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_rp_extension::where('wp_work_id',$wp_work_id)->where('aproc', '!=', 'C')->
            select('id')->where('isClose','N');
        if($isPass == 'Y')
        {
            $data = $data->where('aproc','O');
        }

        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }
    //是否存在
    protected  function getChargeInfo($id)
    {
        if(!$id) return [0,'',0,0];
        $data = wp_work_rp_extension::where('id',$id)->select('wp_work_id','aproc','charge_dept1','charge_dept2')->where('isClose','N');

        $data = $data->first();
        $wp_work_id     = (isset($data->wp_work_id))? $data->wp_work_id : 0;
        $aproc          = (isset($data->aproc))? $data->aproc : '';
        $charge_dept1   = (isset($data->charge_dept1))? $data->charge_dept1 : 0;
        $charge_dept2   = (isset($data->charge_dept2))? $data->charge_dept2 : 0;
        return [$wp_work_id,$aproc,$charge_dept1,$charge_dept2];
    }

    //是否存在
    protected  function getExtensionData($wp_work_id)
    {
        if (!$wp_work_id) return 0;
        $data = wp_work_rp_extension::where('wp_work_id', $wp_work_id)->where('aproc', '!=', 'C')
        ->leftjoin('b_cust as b','wp_work_rp_extension.charge_user1','b.id')
        ->leftjoin('b_cust as c','wp_work_rp_extension.charge_user2','c.id')
        ->leftjoin('b_cust as d','wp_work_rp_extension.apply_user','d.id')
        ->select('b.name as charge_name1','c.name as charge_name2','d.name as charge_name3','b.sign_img as img1','c.sign_img as img2','wp_work_rp_extension.*')
        ->where('wp_work_rp_extension.isClose', 'N')
        ->first();

        return (isset($data)) ? $data : '';
    }

}
