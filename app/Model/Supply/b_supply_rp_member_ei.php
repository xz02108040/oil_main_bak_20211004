<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_member_ei extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_member_ei';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($uid, $tid, $exid = 0)
    {
        if(!$uid || !$tid) return 0;
        $data = b_supply_rp_member_ei::where('b_cust_id',$uid)->where('engineering_identity_id',$tid)->where('isClose','N');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

    //取得 名稱
    protected  function getApplyDate($uid,$engineering_identity_id)
    {
        if(!$uid || !$engineering_identity_id) return '';
        $data = b_supply_rp_member_ei::where('b_cust_id',$uid)->where('engineering_identity_id',$engineering_identity_id)->where('isClose','N');
        $data = $data->where('aproc','A')->first();

        return isset($data->id)? substr($data->apply_stamp,0,10) : '';
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        return b_supply_rp_member_ei::find($id)->name;
    }

    //取得 下拉選擇全部
    protected  function getSelect()
    {
        $ret    = [];
        $data   = b_supply_rp_member_ei::select('id','name')->where('isClose','N')->get();
        $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
