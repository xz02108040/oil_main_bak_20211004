<?php

namespace App\Model\WorkPermit;

use App\Lib\SHCSLib;
use App\Model\User;
use App\Model\View\view_user;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_work_process extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_work_process';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($work_id,$process_id)
    {
        if(!$work_id || !$process_id) return 0;
        $data = wp_work_process::where('wp_work_id',$work_id)->where('wp_permit_process_id',$process_id)->orderby('id','desc')->first();

        return (isset($data->id))? $data->id : 0;
    }

    //是否已經有人審查
    protected  function isCharge($id)
    {
        if(!$id) return [0,''];
        $data = wp_work_process::where('id',$id)->select('charge_user','charge_stamp')->first();
        return (isset($data->charge_user))? [$data->charge_user,substr($data->charge_stamp,0,16)] : [0,''];
    }

    //負責人
    protected  function getChargeUser($work_id,$permit_process_id = 0)
    {
        if(!$work_id) return 0;
        $data = wp_work_process::where('wp_work_id',$work_id)->where('wp_permit_process_id',$permit_process_id)->where('isClose','N')->first();
        return isset($data->id)? $data->charge_user : 0;
    }
    //找上一個階段ID
    protected  function getLastProcessID($wp_work_id,$wp_work_list_id,$now_work_process_id,$last_loop_max_time = 1)
    {
        $loop_time = 1;
        $work_process_id = $wp_permit_process_id = 0;
        $data = wp_work_process::where('wp_work_id',$wp_work_id)->where('wp_work_list_id',$wp_work_list_id)->
            where('id','!=',$now_work_process_id)->
        select('id','wp_permit_process_id')->orderby('id','desc');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $wp_permit_process_id = $val->wp_permit_process_id;
                $work_process_id = $val->id;

                if($loop_time == $last_loop_max_time) break;
                $loop_time++;
            }
        }
        return [$work_process_id,$wp_permit_process_id];
    }


    //取得 名稱
    protected  function getRejectStatusList($id)
    {
        $ret = ['N',''];
        if(!$id) return $ret;
        $data = wp_work_process::find($id);
        if(isset($data->id) && strlen($data->charge_memo))
        {
            $reject_type = wp_permit_process::getRuleReject($data->wp_permit_process_id);
            $chargeMemo  = ($reject_type == 2)?'base_10939' : 'base_10941';

            if($data->wp_permit_process_id == 21) $chargeMemo = 'base_10951';
            if($data->wp_permit_process_id == 23) $chargeMemo = 'base_10953';
            if($data->wp_permit_process_id == 25) $chargeMemo = 'base_10955';

            $reject_user = User::getName($data->mod_user) ;
            $reject_stamp= $data->updated_at;
            $reject_memo = Lang::get('sys_base.'.$chargeMemo,['name1'=>$reject_user,'name2'=>$reject_stamp,'name3'=>$data->charge_memo]);
            $ret = ['Y',$reject_memo];
        }
        return $ret;
    }

    //取得 名稱
    protected  function getProcessList($work_process_id)
    {
        if(!$work_process_id) return [0,'',0,'',''];
        $data = wp_work_process::join('wp_permit_process as p','p.id','=','wp_work_process.wp_permit_process_id')->
                where('wp_work_process.id',$work_process_id)->
                where('wp_work_process.isClose','N')->select('p.*','wp_work_process.charge_user','wp_work_process.stime','wp_work_process.etime');

        $data = $data->orderby('id','desc')->first();
        return (isset($data->id))? [$data->id,$data->title,$data->charge_user,$data->stime,$data->etime] : [0,'',0,'',''];
    }

    //取得 負責人
    protected  function getCharger($id)
    {
        if(!$id) return 0;
        $data = wp_work_process::find($id);
        return (isset($data->id))? $data->charge_user : 0;
    }

    //取得 名稱
    protected  function getProcess($id)
    {
        if(!$id) return 0;
        $data = wp_work_process::find($id);
        return (isset($data->id))? $data->wp_permit_process_id : 0;
    }

    //取得 該工作許可證之執行單 已經執行的階段
    protected  function getListProcess($list_id,$isActive = 1,$type = 1)
    {
        $ret = [];
        if(!$list_id) return $ret;
        $data = wp_work_process::where('wp_work_list_id',$list_id)->where('isClose','N');

        if($isActive)
        {
            $data = $data-> where('charge_user','!=',0);
        }
        $data = $data->select('id','wp_permit_process_id')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                if($type == 2)
                {
                    $ret[$val->id] = $val->wp_permit_process_id;
                } else {
                    $ret[$val->wp_permit_process_id] = $val->wp_permit_process_id;
                }
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getChargerSelect($work_id, $bctype = 0, $wp_permit_process_id = 0)
    {
        $ret    = [];
        $data   = wp_work_process::where('wp_work_id',$work_id)->where('charge_user','!=',0)->where('isClose','N');
        if($wp_permit_process_id)
        {
            $data = $data->where('wp_permit_process_id',$wp_permit_process_id);
        }

        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $key => $val)
            {
                $isOk = 1;
                if($bctype && view_user::getBcType($val->charge_user) != $bctype) $isOk = 0;

                if($isOk) $ret[$val->charge_user] = $val->charge_user;
            }
        }

        return $ret;
    }

    //查詢指定階段出現幾次
    protected  function getProcessAmt($work_id,$permit_process_id)
    {
        if(!$work_id || !$permit_process_id) return 0;
        return wp_work_process::where('wp_work_id',$work_id)->where('wp_permit_process_id',$permit_process_id)->where('isClose','N')->count();
    }
}
