<?php

namespace App\Model\Factory;

use App\Model\Emp\be_dept;
use App\Model\Supply\b_supply;
use App\Model\User;
use App\Model\View\view_used_rfid;
use Illuminate\Database\Eloquent\Model;
use Lang;

class b_rfid_a extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'b_rfid_a';
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
    protected function isExist($id = 0 , $code = '')
    {
        if(!$id && !$code) return 0;
        $today = date('Y-m-d');
        $data  = b_rfid_a::where('id',$id)->where('isClose','N')->where('edate','>=',$today);
        return $data->count();
    }

    /**
     *  [人員]配卡資格是否符合
     * @param $id
     * @return boolean
     */
    protected function isUsedCloseCard($uid)
    {
        if(!$uid) return false;
        $data  = b_rfid_a::join('b_rfid as b','b.id','b_rfid_a.b_rfid_id')->
        where('b_cust_id',$uid)->where('b_rfid_a.isClose', 'N')->
        where('b.isClose','Y')->
        first();
        return isset($data->b_cust_id) ? true : false;
    }

    /**
     *  使用卡片相對應的數值
     * @param $id
     * @return int
     */
    protected function getUsedUser($b_rfid_a_id)
    {
        if (!$b_rfid_a_id) return 0;
        $data = b_rfid_a::where('b_rfid_a.id', $b_rfid_a_id)->where('b_rfid_a.isClose', 'N')->
        select('b_rfid_a.b_cust_id')->first();
        return (isset($data->b_cust_id))? $data->b_cust_id : 0;
    }

    /**
     *  使用卡片相對應的數值
     * @param $id
     * @return int
     */
    protected function getUsedCnt($b_rfid_a_id)
    {
        if(!$b_rfid_a_id) return '';
        $data  = b_rfid_a::join('b_rfid as r','r.id','=','b_rfid_a.b_rfid_id')->
                where('b_rfid_a.id',$b_rfid_a_id)->where('b_rfid_a.isClose','N')->orderby('b_rfid_a.id','desc')->
                select('b_rfid_a.*','r.rfid_type')->first();
        if(!isset($data->id)) return '';
        $dept   = be_dept::getName($data->be_dept_id);
        $supply = b_supply::getName($data->b_supply_id);
        $car    = b_car::getNo($data->b_car_id);
        $store  = b_factory::getName($data->b_factory_id);
        $local  = b_factory_a::getName($data->b_factory_a_id);
        $user   = User::getName($data->b_cust_id);
        switch($data->rfid_type)
        {
            //人員
            case '1':
            case '5':
                $ret  = ($data->rfid_type == 5)? $supply : $dept;
                $ret .= "：".$user;
                break;
            //車輛
            case '2':
            case '6':
                $ret  = ($data->rfid_type == 6)? $supply : $dept;
                $ret .= "：".$car;
                break;
            //場地
            case '3':
                $ret  = $store."：".$local;
                break;
            //訪客
            case '4':
                $ret  = $store ."：".$local;
                break;
            default:
                $ret = $store;
                break;
        }

        return $ret;
    }
}
