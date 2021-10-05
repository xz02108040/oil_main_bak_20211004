<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_check_record2 extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_check_record2';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($work_id,$wp_work_list_id,$wp_work_process_id,$wp_check_kind_id,$b_cust_id,$door_type = 1)
    {
        if(!$work_id) return 0;
        $data = wp_work_check_record2::where('wp_work_id',$work_id)->where('wp_work_list_id',$wp_work_list_id)->
        where('wp_work_process_id',$wp_work_process_id)->where('wp_check_kind_id',$wp_check_kind_id);
        $data = $data->where('b_cust_id',$b_cust_id)->where('door_type',$door_type)->select('id')->orderby('id','desc')->first();
        return isset($data->id)? $data->id : 0;
    }
    //是否存在
    protected  function getLastRecord($work_id, $wp_check_id = 0)
    {
        $ret = [];
        $ret['record_user1'] = '';
        $ret['record_stamp1'] = '';
        $ret['record_alert1'] = '';
        $ret['record_stamp2'] = '';
        $ret['record1'] = '';
        $ret['record2'] = '';
        $ret['record3'] = '';
        $ret['record4'] = '';
        $ret['record5'] = '';
        $ret['record1_check'] = '';
        $ret['record2_check'] = '';
        $ret['record3_check'] = '';
        $ret['record4_check'] = '';
        if(!$work_id) return $ret;
        $no = 1;


        $data = wp_work_check_record2::where('wp_work_id',$work_id);
        if(is_array($wp_check_id))
        {
            $data = $data->whereIn('wp_check_id',$wp_check_id);
        }elseif($wp_check_id)
        {
            $data = $data->where('wp_check_id',$wp_check_id);
        }
        $data = $data->orderby('id','desc')->limit(2);
        if($data->count()) {
            $data = $data->get();
            foreach ($data as $val) {
                if ($no == 1) {
                    $stamp1 = '';
                    $stamp_alert = '';
                    if ($val->record_stamp) {
                        $stamp1 = substr($val->record_stamp, 11, 5);
                        if ((time() - strtotime($val->record_stamp)) >= 3600) {
                            $stamp_alert = 'redAlert';
                        }
                    }

                    $ret['record_user1'] = User::getName($val->record_user);
                    $ret['record_stamp1'] = $stamp1;
                    $ret['record_alert1'] = $stamp_alert;
                    $ret['record1'] = $val->record1;
                    $ret['record2'] = $val->record2;
                    $ret['record3'] = $val->record3;
                    $ret['record4'] = $val->record4;
                    $record1_check = SHCSLib::isPermitCheckOverLimit('record1', $val->record1);
                    $record2_check = SHCSLib::isPermitCheckOverLimit('record2', $val->record2);
                    $record3_check = SHCSLib::isPermitCheckOverLimit('record3', $val->record3);
                    $record4_check = SHCSLib::isPermitCheckOverLimit('record4', $val->record4);
                    $ret['record1_check'] = (!$record1_check) ? 'redAlert' : '';
                    $ret['record2_check'] = (!$record2_check) ? 'redAlert' : '';
                    $ret['record3_check'] = (!$record3_check) ? 'redAlert' : '';
                    $ret['record4_check'] = (!$record4_check) ? 'redAlert' : '';
                    $ret['record5'] = ($val->record5n) ? ($val->record5n . ':' . $val->record5) : '';
                } else {
                    $ret['record_stamp2'] = ($val->record_stamp) ? substr($val->record_stamp, 11, 5) : '';
                }
                $no++;
            }
        }
        return $ret;
    }
}
