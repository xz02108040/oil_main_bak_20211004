<?php

namespace App\Model\Engineering;

use App\Model\Factory\b_car;
use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Lang;

class e_violation_contractor extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'e_violation_contractor';
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
     *  是否存在[工程案件]
     * @param $id
     * @return int
     */
    protected function isProjectExist($id)
    {
        if(!$id) return 0;
        $data  = e_violation_contractor::where('e_project_id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在[承攬商]
     * @param $id
     * @return int
     */
    protected function isSupplyExist($id)
    {
        if(!$id) return 0;
        $data  = e_violation_contractor::where('b_supply_id',$id)->where('isClose','N');
        return $data->count();
    }
    /**
     *  是否存在[承攬商成員]
     * @param $id
     * @return int
     */
    protected function isMemberExist($bcid, $type = 1)
    {
        $ret = ($type == 2)? '' : 0;
        if(!$bcid) return $ret;
        $data  = e_violation_contractor::where('b_cust_id',$bcid)->where('isControl','Y')->where('limit_edate','>=',date('Y-m-d'))->where('isClose','N');
        //2019-08-16 新增 逃避規則：到期日=開罰日期，等於不用除罰
        $data  = $data->whereRaw('limit_edate != limit_sdate');
        $data  = $data->first();

        return isset($data->id)? (($type == 2)? $data->violation_record1.'('.$data->limit_edate.')' : $data->id) : $ret;
    }
    /**
     *  取得人員違規事項
     * @param $id
     * @return int
     */
    protected function getName($id)
    {
        if(!$id) return 0;
        $name  = '';
        $data  = e_violation_contractor::find($id);
        if(!isset($data->id)) return $name;

        $user = User::getName($data->b_cust_id);
        $listAry = ['user'=>$user,'v1'=>$data->violation_record1,'v2'=>$data->violation_record3,'sdate'=>$data->limit_sdate,'edate'=>$data->limit_edate];
        $name = Lang::get('sys_engineering.engineering_1020',$listAry);
        return $name;
    }
    /**
     *  取得人員違規事項
     * @param $id
     * @return int
     */
    protected function getViolationAmt($today = '')
    {
        if(!$today) $today = date('Y-m-d');
        $data  = e_violation_contractor::where('isControl','Y')->where('isClose','N')->where('limit_sdate','<=',$today)->where('limit_edate','>=',$today);

        return $data->count();
    }

    //取得 [汽車違規]
    protected  function getCarSelect()
    {
        $ret = [];
        $data = e_violation_contractor::where('isClose','N')->
        where('isControl','Y')->where('e_violation_complain_id',0)->
        where('limit_edate','>',date('Y-m-d'))->where('b_car_id','>',0)->
        select('b_car_id','violation_record1','violation_record3','limit_sdate','limit_edate');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                $car_no  = b_car::getNo($val->b_car_id);
                $listAry = ['user'=> $car_no,'v1'=>$val->violation_record1,'v2'=>$val->violation_record3,'sdate'=>$val->limit_sdate,'edate'=>$val->limit_edate];
                $ret[$val->b_car_id] = Lang::get('sys_engineering.engineering_1020',$listAry);
            }
        }
        return $ret;
    }

    //取得 下拉選擇全部
    protected  function getSelect($pid,$sid,$targetType = 1,$isFirst = 1)
    {
        $ret  = [];
        $data = e_violation_contractor::where('isClose','N')->
        where('isControl','Y')->where('e_violation_complain_id',0)->
        where('limit_edate','>',date('Y-m-d'));

        if($pid)
        {
            $data = $data->where('e_project_id',$pid);
        }
        if($sid)
        {
            $data = $data->where('b_supply_id',$sid);
        }
        if($targetType == 2)
        {
            $data = $data->where('b_car_id','>',0);
        } else {
            $data = $data->where('b_cust_id','>',0);
        }
        $data = $data->orderby('limit_edate')->get();

        if($isFirst) $ret[0] = Lang::get('sys_base.base_10015');

        foreach ($data as $key => $val)
        {
            if(!e_violation_complain::isExist($val->id))
            {
                $user = ($targetType == 2)? b_car::getNo($val->b_car_id) : User::getName($val->b_cust_id);
                $listAry = ['user'=>$user,'v1'=>$val->violation_record1,'v2'=>$val->violation_record3,'sdate'=>$val->limit_sdate,'edate'=>$val->limit_edate];
                $name = Lang::get('sys_engineering.engineering_1020',$listAry);
                $ret[$val->id] = $name;
            }
        }

        return $ret;
    }



}
