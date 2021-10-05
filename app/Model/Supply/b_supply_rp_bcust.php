<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_bcust extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_bcust';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($sid, $uid, $exid = 0)
    {
        if(!$uid || !$sid) return 0;
        $data = b_supply_rp_bcust::where('b_supply_id',$sid)->where('b_cust_id',$uid)->where('aproc','!=','C');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }
}
