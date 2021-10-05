<?php

namespace App\Model\View;

use Lang;
use App\Model\User;
use App\Model\sys_param;
use App\Model\Emp\be_dept;
use App\Model\Factory\b_car;
use App\Model\Supply\b_supply;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class view_used_rfid extends Model
{
    /**
     * 使用者Table: 列出正在配對中的ＲＦＩＤ
     */
    protected $table = 'view_used_rfid';
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
     *  卡片是否已存在[使用中]
     * @param $id (b_rfid_id)
     * @return int
     */
    protected function isUsed($id,$uid = 0)
    {
        if(!$id) return 0;
        $data  = view_used_rfid::where('id',$id);
        if($uid)
        {
            $data = $data->where('b_cust_id',$uid);
        }
        return $data->count();
    }

    /**
     *  卡片是否已存在[人員]
     * @param $id
     * @return int
     */
    protected function isUserExist($uid)
    {
        if(!$uid) return  [0,'',''];
        $data  = view_used_rfid::where('b_cust_id',$uid)->select('id','rfid_code','b_rfid_a_id')->first();
        return isset($data->id)? [$data->id,$data->name,$data->rfid_code,$data->b_rfid_a_id] : [0,'',''];
    }

    /**
     *  [人員]配卡資格是否符合
     * @param $id
     * @return boolean
     */
    protected function isUserRFID($uid)
    {
        if(!$uid) return false;
        $data  = view_used_rfid::where('b_cust_id',$uid)->where('edate', '>=',DB::raw('GETDATE()'))->first();
        return isset($data->b_cust_id) ? true : false;
    }

    /**
     *  該承攬商公司是否已經配卡超過30人
     * @param $id
     * @return int
     */
    protected function isSupplyOverPairCardMaxNum($supply_id)
    {
        if(!$supply_id) return  true;
        //系統參數
        $maxNum = sys_param::getParam('SUPPLY_MAX_PARICARD_NUMBER',30);

        $supplyNowNum  = view_used_rfid::where('b_supply_id',$supply_id)->count();
        return ($supplyNowNum >= $maxNum)? true : false;
    }

    /**
     * 檢查特定卡片內碼與流水號是否被使用
     * @param $id
     * @return int
     */
    protected function isExistRfidCode($rfid_code,$rfid_name = '',$type = 0)
    {
        if(!$rfid_code && !$rfid_name) return 0;

        $data  = view_used_rfid::select('id');
        if($rfid_code)
        {
            $data = $data->where('rfid_code',$rfid_code);
        }
        if($rfid_name)
        {
            $data = $data->where('name',$rfid_name);
        }
        if($type)
        {
            $data = $data->where('rfid_type',$type);
        }
        $data  = $data->first();
        return isset($data->id)? $data->id : 0;
    }

    /**
     *  使用卡片相對應的數值
     * @param $id
     * @return int
     */
    protected function getUsedCnt($b_rfid_a_id)
    {
        if(!$b_rfid_a_id) return '';
        $data  = view_used_rfid::where('b_rfid_a_id',$b_rfid_a_id)->first();
        if(!isset($data->id)) return '';

        switch($data->rfid_type)
        {
            //人員
            case '1':
            case '5':
                $ret  = ($data->rfid_type == 5)? b_supply::getName($data->b_supply_id) : be_dept::getName($data->be_dept_id);
                $ret .= "：".User::getName($data->b_cust_id);
                break;
            //車輛
            case '2':
            case '6':
                $ret  = ($data->rfid_type == 6)? b_supply::getName($data->b_supply_id) : be_dept::getName($data->be_dept_id);
                $ret .= "：".b_car::getNo($data->b_car_id);
                break;
            //場地
            case '3':
                $ret  = b_factory::getName($data->b_factory_id);
                $ret .= "：".b_factory_a::getName($data->b_factory_a_id);
                break;
            //訪客
            case '4':
                $ret  = b_factory::getName($data->b_factory_id);
                $ret .= "：".b_factory_a::getName($data->b_factory_a_id);
                break;
            default:
                $ret = b_factory::getName($data->b_factory_id);
                break;
        }

        return $ret;
    }

    /**
     *  取得 使用名單
     * @param $id
     * @return int
     */
    protected function getSelect($rfid_type,$supply_id = 0)
    {
        $ret    = [];
        $keyAry = [1=>'b_cust_id',2=>'b_car_id',3=>'b_factory_a_id',4=>'b_factory_a_id',5=>'b_cust_id',6=>'b_car_id'];
        $key = isset($keyAry[$rfid_type])? $keyAry[$rfid_type] : '';
        if(!$rfid_type || !$key) return $ret;

        $data =  view_used_rfid::where('rfid_type',$rfid_type);
        if($rfid_type == 5 && $supply_id)
        {
            $data = $data->where('b_supply_id',$supply_id);
        }
        $data = $data->get();
        if(count($data))
        {
            foreach ($data as $val)
            {
                $ret[$val->$key] = $val->$key;
            }
        }

        return $ret;
    }
}
