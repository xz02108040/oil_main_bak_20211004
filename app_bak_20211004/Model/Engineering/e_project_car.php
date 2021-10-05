<?php

namespace App\Model\Engineering;

use App\Lib\HtmlLib;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\sys_param;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_project_car extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_project_car';
    /**
     * Table Index:
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $guarded = ['id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($project_id,$car_id = 0,$extid = 0)
    {
        if(!$project_id) return 0;
        $data  = e_project_car::where('e_project_id',$project_id)->where('isClose','N');
        if($car_id)
        {
            $data = $data->where('b_car_id',$car_id);
        }
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isCarNoExist($car_no,$extid = 0)
    {
        if(!$car_no) return 0;
        $data  = e_project_car::join('b_car as a','a.id','e_project_car.b_car_id')->
                where('a.car_no',$car_no)->where('e_project_car.isClose','N');
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return $data->count();
    }
    
    /**
     *  車牌號碼
     * @param $id
     * @return int
     */
    protected function getName($car_id)
    {
        if(!$car_id) return '';
        $data  = e_project_car::join('b_car as f','f.id','=','e_project_car.b_car_id')->
                where('b_car_id',$car_id)->where('e_project_car.isClose','N')->select('f.car_no')->first();
        return isset($data->car_no)? $data->car_no : '';
    }

    /**
     *  取得工程案件之所有車牌號碼
     * @param $project_id
     * @return int
     */
    protected function getNameAry($car_id)
    {
        if(!$car_id) return '';
        $storeAry = [];

        $data  = e_project_car::
                join('b_car as f','f.id','=','e_project_car.b_car_id')->
                where('e_project_car.b_car_id',$car_id)->where('e_project_car.isClose','N')->select('f.car_no')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = $val->car_no;
            }
        }

        return implode('，',$storeAry);
    }

     /**
     *  取得工程案件之所有工程案件
     * @param $project_id
     * @return int
     */
    protected function getProjectAry($car_id)
    {
        if(!$car_id) return '';
        $storeAry = [];

        $data  = e_project_car::
                join('b_car as f','f.id','=','e_project_car.b_car_id')->
                where('e_project_car.b_car_id',$car_id)->where('e_project_car.isClose','N')->select('e_project_car.e_project_id')->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $storeAry[] = e_project::getName($val->e_project_id,2);
            }
        }
        
        return implode('，',$storeAry);
    }

    //取得 下拉選擇全部
    protected  function getSelect($project_id,$isFirst = 1,$type = 1)
    {
        $ret  = [];
        $data = e_project_car::where('e_project_id',$project_id)->select('b_car_id')->where('isClose','N');
        $data = $data->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if($type)
            {
                $ret[$val->b_car_id] = b_car::getNo($val->b_car_id);
            } else {
                $ret[] = $val->b_car_id;
            }
        }

        return $ret;
    }

    /**
     *  取得id
     * @param $id
     * @return int
     */
    protected function getId($car_id = 0,$extid = 0)
    {
        if(!$car_id) return 0;
        $data  = e_project_car::where('b_car_id',$car_id)->where('isClose','N')->first();
        if($extid)
        {
            $data = $data->where('id','!=',$extid);
        }
        return isset($data->id) ? $data->id : 0;
    }

}
