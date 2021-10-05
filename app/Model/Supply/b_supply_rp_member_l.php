<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_member_l extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_member_l';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($uid, $tid, $exid = 0)
    {
        if(!$uid || !$tid) return 0;
        $data = b_supply_rp_member_l::where('b_cust_id',$uid)->where('e_license_id',$tid)->where('isClose','N');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

    //取得 檔案
    protected  function getFile($id,$code = 'A')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_supply_rp_member_l::find($id);
        if(isset($data->id))
        {
            if($code == 'C')
            {
                $ret = $data->file3;
            }
            elseif($code == 'B')
            {
                $ret = $data->file2;
            }
            else {
                $ret = $data->file1;
            }
        }
        return $ret;
    }
}
