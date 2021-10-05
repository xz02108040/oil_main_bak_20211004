<?php

namespace App\Model\Supply;

use Lang;
use App\Model\User;
use App\Lib\SHCSLib;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Supply\SupplyRPProjectTrait;

class b_supply_rp_project extends Model
{
    use SupplyRPProjectTrait;

    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_project';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($sid, $uid, $exid = 0)
    {
        if(!$uid || !$sid) return 0;
        $data = b_supply_rp_project::where('b_supply_id',$sid)->where('b_cust_id',$uid)->where('aproc','!=','C');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

     //人員轉公司，取消加入案件申請單
     protected  function cancelSuppyRPProject($b_cust_id, $mod_user = 1)
     {
         if(!$b_cust_id) return 0;
         $ret = 0;
         $data = b_supply_rp_project::where('b_cust_id',$b_cust_id)->
         whereNotIn('aproc', array('O','C'))->get();
 
         $tmp = [];
         foreach ($data as $val) {
             $UPD = b_supply_rp_project::find($val->id);
             $tmp['aproc'] = 'C';
             $ret = $this->setSupplyRPProject($UPD->id,$tmp,$mod_user);
         }
         return $ret;
     }
}
