<?php

namespace App\Model\WorkPermit;

use App\Lib\HTTCLib;
use App\Lib\SHCSLib;
use App\Model\bc_type_app;
use Illuminate\Database\Eloquent\Model;
use Lang;

class wp_permit_process_target extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'wp_permit_process_target';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($pid, $bc_type_app, $extid = 0)
    {
        if(!$pid || !$bc_type_app) return 0;
        $data = wp_permit_process_target::where('wp_permit_process_id',$pid)->where('bc_type_app_id',$bc_type_app)->
                where('isClose','N');

        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();

        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getTarget($process_id,$retType = 1,$workList = [0,0,0,0,0,[],[]])
    {
        $ret = 0;
        $app = [];
        if(!$process_id) return $ret;

        $data = wp_permit_process_target::select('bc_type_app_id')->
                where('wp_permit_process_id',$process_id)->where('isClose','N')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $app[] = $val->bc_type_app_id;
            }
        }
        $ret = HTTCLib::genPermitTargetName($app,$retType,$workList);
//        dd($process_id,$app,$ret);
        return $ret;
    }

    //取得 名稱
    protected  function getIsAdminDept($permit,$process_id)
    {
        $ret = 'N';
        if(!$process_id) return $ret;

        $data = wp_permit_process_target::select('rule_admindept')->
                where('wp_permit_id',$permit)->
                where('wp_permit_process_id',$process_id)->where('isClose','N');
        if($data->count())
        {
            $data = $data->first();
            $ret = $data->rule_admindept;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($process_id,$isFirst = 1)
    {
        $ret    = [];
        $data   = wp_permit_process_target::join('bc_type_app as a','a.id','=','wp_permit_process_target.bc_type_app_id')->
        where('wp_permit_process_id',$process_id)->select('bc_type_app_id','a.name')->
        where('wp_permit_process_target.isClose','N')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->bc_type_app_id] = ($val->name);
        }

        return $ret;
    }
}
