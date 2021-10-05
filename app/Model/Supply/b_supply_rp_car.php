<?php

namespace App\Model\Supply;

use App\Lib\SHCSLib;
use App\Model\Factory\b_car;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_supply_rp_car extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_supply_rp_car';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($sid, $no, $exid = 0)
    {
        if(!$no || !$sid) return 0;
        $data = b_supply_rp_car::where('b_supply_id',$sid)->where('car_no',$no)->where('aproc','!=','C');
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }

        return $data->count();
    }

    //車牌照片
    protected function getImg($id,$paramid = 'D')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_supply_rp_car::find($id);

        if(isset($data->img_path))
        {
            if($paramid == 'A')
            {
                $ret = $data->file1;
            }
            if($paramid == 'B')
            {
                $ret = $data->file2;
            }
            if($paramid == 'C')
            {
                $ret = $data->file3;
            }
            if($paramid == 'D')
            {
                $ret = $data->img_path;
            }
        }
        return $ret;
    }

    //取得 檔案
    protected  function getFile($id,$code = 'A')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_supply_rp_car::find($id);
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
