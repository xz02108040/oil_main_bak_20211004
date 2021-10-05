<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_rp_tranuser_a extends Model
{
    /**
     * Table: 工單延長申請單
     */
    protected $table = 'wp_work_rp_tranuser_a';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($wp_work_id, $isPass = 'N')
    {
        if(!$wp_work_id) return 0;
        $data = wp_work_rp_tranuser_a::where('wp_work_id',$wp_work_id)->where('aproc', '!=', 'C')->
            select('id')->where('isClose','N');
        if($isPass == 'Y')
        {
            $data = $data->where('aproc','O');
        }

        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    protected function getAry($wp_work_rp_tranuser_id)
    {
        $ret  = [];
        $data = wp_work_rp_tranuser_a::where('wp_work_rp_tranuser_id',$wp_work_rp_tranuser_id)->
        select('b_cust_id','wp_work_worker_id')->where('isClose','N');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $workerdata = wp_work_worker::find($val->wp_work_worker_id);
                $tmp   = [];
                $tmp['user_id']         = $val->b_cust_id;
                $tmp['in_time']         = isset($workerdata->in_time)? $workerdata->in_time : 0;
                $tmp['out_time']        = isset($workerdata->out_time)? $workerdata->out_time : 0;
                $tmp['door_total_time'] = isset($workerdata->door_total_time)? $workerdata->door_total_time : 0;
                $tmp['work_total_time'] = isset($workerdata->work_total_time)? $workerdata->work_total_time : 0;
                $tmp['door_stime1']     = isset($workerdata->door_stime1)? $workerdata->door_stime1 : '';
                $tmp['door_etime1']     = isset($workerdata->door_etime1)? $workerdata->door_etime1 : '';
                $tmp['door_stime']      = isset($workerdata->door_stime)? $workerdata->door_stime : '';
                $tmp['door_etime']      = isset($workerdata->door_etime)? $workerdata->door_etime : '';
                $tmp['work_stime']      = isset($workerdata->work_stime)? $workerdata->work_stime : '';
                $tmp['work_etime']      = isset($workerdata->work_etime)? $workerdata->work_etime : '';
                $ret[] = $tmp;
            }
        }
        return $ret;
    }

}
