<?php

namespace App\Model\Factory;

use App\Lib\SHCSLib;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_car extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_car';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    //是否存在
    protected  function isExist($no, $exid = 0, $isClose = 'N')
    {
        if(!$no) return 0;
        $data = b_car::where('car_no',$no);
        if($exid)
        {
            $data = $data->where('id','!=',$exid);
        }
        if($isClose)
        {
            $data = $data->where('isClose',$isClose);
        }

        return $data->count();
    }

    //車牌
    protected function getNo($id)
    {
        if(!$id) return '';
        $data = b_car::find($id);

        return isset($data->car_no)? $data->car_no : '';
    }

    //車輛ID
    protected function getID($no)
    {
        if (!$no) return '';
        $data = b_car::where('car_no', $no)->where('isClose', 'N')->first();

        return isset($data->id) ? $data->id : 0;
    }

    //種類
    protected function getType($id, $isName = 1)
    {
        $ret = '';
        if(!$id) return $ret;
        $carAry = b_car_type::getSelect();
        $data = b_car::find($id);
        if(isset($data->car_type))
        {
            $ret = ($isName && isset($carAry[$data->car_type]))? $carAry[$data->car_type] : $data->car_type;
        }
        return $ret;
    }

    //車牌照片
    protected function getImg($id,$paramid = 'D')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_car::find($id);

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

    //取得 下拉選擇全部
    protected  function getSelect($dept = 0, $supply = 0, $isFirst = 1)
    {
        $ret    = [];
        $data   = b_car::select('id','car_no')->where('isClose','N');
        if($dept)
        {
            $data = $data->where('be_dept_id',$dept);
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        $data   = $data->orderby('car_no')->get();
        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            $user = User::getName($val->b_cust_id);
            $ret[$val->id] = $user.$val->car_no;
        }

        return $ret;
    }

    //取得 檔案
    protected  function getFile($id,$code = 'A')
    {
        $ret = '';
        if(!$id) return $ret;
        $data = b_car::find($id);
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
