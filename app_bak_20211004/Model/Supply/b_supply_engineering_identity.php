<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\Engineering\e_project_license;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_engineering_identity extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_engineering_identity';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($name, $extid = 0)
    {
        if(!$name) return 0;
        $data = b_supply_engineering_identity::where('name',$name);
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        $data = $data->first();
        return (isset($data->id))? $data->id : 0;
    }

    //取得 名稱
    protected  function getName($id)
    {
        if(!$id) return '';
        $data = b_supply_engineering_identity::find($id);
        return isset($data->name)? $data->name : '';
    }
    //取得 審查類型 1:監造 2:工安
    protected  function getChargeKind($id)
    {
        if(!$id) return 1;
        $data = b_supply_engineering_identity::find($id);
        return isset($data->charge_kind)? $data->charge_kind : 1;
    }

    //取得 下拉選擇全部
    protected  function getSelect($isFirst = 1, $extAry = [])
    {
        $ret    = [];
        $data   = b_supply_engineering_identity::select('id','name')->where('isClose','N');
        if(count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data   = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getApplySelect($e_project_id,$extAry = [],$ext_user = 0)
    {
        $ret    = $userIdentityAry = [];
        $allowIdentity = e_project_license::getSelect($e_project_id,1);
        $allowIdentityAry = array_keys($allowIdentity);
        $data   = b_supply_engineering_identity::select('id','name')->where('isClose','N');
        if(count($allowIdentityAry))
        {
            $data = $data->whereIn('id',$allowIdentityAry);
        }
        if(count($extAry))
        {
            $data = $data->whereNotIn('id',$extAry);
        }
        $data   = $data->orderby('show_order')->get();
        if(!count($data)) return $ret;

        foreach ($data as $key => $val)
        {
            $memo    = '';
            $isApply = 'N';
            //如果有針對某人，找出該人的工程身份，排除
            if($ext_user)
            {
                if($edate = b_supply_member_ei::getEdate($ext_user,$val->id))
                {
                    $isApply = 'Y'; //已經存在
                    $memo    = Lang::get('sys_base.base_10143',['edate'=>$edate]);
                } elseif($edate = b_supply_rp_member_ei::getApplyDate($ext_user,$val->id))
                {
                    $isApply = 'Y'; //已經申請中
                    $memo    = Lang::get('sys_base.base_10144',['edate'=>$edate]);
                }
            }


            //list($licenseAllName,$licenseAry) = b_supply_engineering_identity_a::getApplySelect($val->id,[],$isApply);
            $ret[$val->id]['id']                = $val->id;
            $ret[$val->id]['name']              = $val->name;
            $ret[$val->id]['isApply']           = $isApply;
            $ret[$val->id]['licenseAllName']    = '';
            $ret[$val->id]['license']           = [];
            $ret[$val->id]['memo']              = $memo;
        }

        return $ret;
    }

    //取得 下拉選擇全部[該成員已申請/尚未申請]
    protected  function getMemberSelect($kind = 1, $uid, $isFirst = 1)
    {
        $ret    = [];
        $applyIdentityAry = b_supply_member_ei::getMemberSelect($uid,0);
        $applyIdentityAry = array_keys($applyIdentityAry);
        //dd([$uid,$applyIdentityAry]);

        $data   = b_supply_engineering_identity::select('id','name')->where('isClose','N');
        //已經申請的工程身份
        if($kind == 1 && count($applyIdentityAry))
        {
            $data = $data->whereIn('id',$applyIdentityAry);
        }
        //排除已經申請的工程身份
        if($kind == 2 && count($applyIdentityAry))
        {
            $data = $data->whereNotIn('id',$applyIdentityAry);
        }
        $data   = $data->orderby('show_order')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $ret[$val->id] = $val->name;
        }

        return $ret;
    }
}
