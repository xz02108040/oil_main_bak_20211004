<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_new_license extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_new_license';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($tname, $exid = 0)
    {
        if(!$tname) return 0;
        $data = b_supply_rp_new_license::whereIn('aproc',['A','O'])->where('license_name',$tname)->where('isClose','N');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

}
