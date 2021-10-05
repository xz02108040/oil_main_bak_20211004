<?php

namespace App\Model\Supply;

use Lang;
use App\Model\User;
use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Supply\SupplyRPProjectLicenseTrait;

class b_supply_rp_project_license extends Model
{
    use SupplyRPProjectLicenseTrait;

    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_project_license';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($e_project_id,$uid, $b_supply_member_l_id, $engineering_identity_id, $aproc = '', $exid = 0)
    {
        if(!$e_project_id ||!$uid || !$engineering_identity_id || !$b_supply_member_l_id) return 0;
        $data = b_supply_rp_project_license::where('e_project_id',$e_project_id)->where('b_cust_id',$uid)->
        where('b_supply_member_l_id',$b_supply_member_l_id)->where('engineering_identity_id',$engineering_identity_id)->
        where('isClose','N');
        if($aproc)
        {
            $data = $data->where('aproc',$aproc);
        }
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

    //是否存在
    protected  function hasRootApply($b_supply_member_l_id)
    {
        if(!$b_supply_member_l_id) return 0;
        $data = b_supply_rp_project_license::
        where('b_supply_member_l_id',$b_supply_member_l_id)->whereIn('engineering_identity_id',[1,2])->
        where('isClose','N');
        $data = $data->whereIn('aproc',['A','P']);

        return $data->count();
    }

    //人員轉公司，取消加入/轉移/作廢工程身分申請單
    protected  function cancelSuppyRPProjectIdentity($b_cust_id, $mod_user = 1)
    {
        if(!$b_cust_id) return 0;
        $ret = 0;
        $data = b_supply_rp_project_license::where('b_cust_id',$b_cust_id)->
        where('isClose','N')->whereNotIn('aproc', array('O','C'))->get();

        $tmp = [];
        foreach ($data as $val) {
            $UPD = b_supply_rp_project_license::find($val->id);
            $tmp['aproc'] = 'C';
            $ret = $this->setSupplyRPProjectMemberLicense($UPD->id,$tmp,$mod_user);
        }
        return $ret;
    }
}
