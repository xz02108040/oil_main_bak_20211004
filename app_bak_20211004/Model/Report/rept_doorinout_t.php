<?php

namespace App\Model\Report;

use App\Model\User;
use App\Lib\SHCSLib;
use Illuminate\Support\Facades\DB;
use App\Model\View\view_log_door_today;
use Illuminate\Database\Eloquent\Model;

class rept_doorinout_t extends Model
{
    /**
     * 使用者Table:
     */
    protected $table = 'rept_doorinout_t';
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
     *  複數人員是否存在
     * @param $id
     * @return int
     */
    protected function isExist($store,$door,$date,$b_cust_id = [],$door_type = -1,$extUser = [],$work_id = 0,$project_id = 0)
    {
        if(!$date) return 0;
        $data  = rept_doorinout_t::where('b_factory_id',$store)->where('door_date',$date);
        
        //2021-08-04 增加篩選條件，檢查在廠內人數時，範圍為今日+往前13個小時
        // $data  = rept_doorinout_t::where('b_factory_id',$store)->
        // whereBetween('door_stamp', array(DB::raw('DATEADD(hh, -13, GETDATE())'), DB::raw('GETDATE()')));

        if(is_numeric($door) && $door > 0)
        {
            $data = $data->where('b_factory_d_id',$door);
        }
        if(is_array($b_cust_id) && count($b_cust_id))
        {
            $data = $data->whereIn('b_cust_id',$b_cust_id);
        }
        if(is_array($extUser) && count($extUser))
        {
            $data = $data->whereNotIn('b_cust_id',$extUser);
        }
        if(isset($work_id))
        {
            $data = $data->where('wp_work_id',$work_id);
        }
        if($project_id)
        {
            $data = $data->where('e_project_id',$project_id);
        }
        if ($door_type >= 0) {
            //當查詢人員是否在場內時，要排除13HR內已經刷出場成功的人員名單
            // if ($door_type == '1') {
            //     $outUser = rept_doorinout_t::where('door_type', '2')->
            //     whereBetween('door_stamp', array(DB::raw('DATEADD(hh, -13, GETDATE())'), DB::raw('GETDATE()')))->
            //     select('b_cust_id')->distinct()->get()->toArray();
            //     $data = $data->whereNotIn('b_cust_id',$outUser);
            // } else {
            $data = $data->where('door_type', $door_type);
            // }
        }
        $data = $data->first();
        return isset($data->id)? $data->id : 0;
    }
    /**
     *  複數人員是否存在
     * @param $id
     * @return int
     */
    protected function getExistAmt($store,$door,$date,$b_cust_id = [],$door_type = -1,$extUser = [],$work_id = 0)
    {
        if(!$date) return 0;
        $data  = rept_doorinout_t::where('b_factory_id',$store)->where('door_date',$date);
        // $date_last = date("Y-m-d", strtotime("$date -1 days"));
        
        //2021-06-09 增加篩選條件，檢查在廠內人數時，範圍為今日+往前13個小時
        // $data  = rept_doorinout_t::where('b_factory_id',$store)->
        // whereBetween('door_stamp', array(DB::raw('DATEADD(hh, -13, GETDATE())'), DB::raw('GETDATE()')));

        if(is_numeric($door) && $door > 0)
        {
            $data = $data->where('b_factory_d_id',$door);
        }
        if(is_array($b_cust_id) && count($b_cust_id))
        {
            $data = $data->whereIn('b_cust_id',$b_cust_id);
        }
        if(is_array($extUser) && count($extUser))
        {
            $data = $data->whereNotIn('b_cust_id',$extUser);
        }
        if($work_id)
        {
            $data = $data->where('wp_work_id',$work_id);
        }
        if ($door_type >= 0) {
            //當查詢人員是否在場內時，要排除13HR內已經刷出場成功的人員名單
            // if ($door_type == '1') {
            //     $outUser = rept_doorinout_t::where('door_type', '2')->
            //     whereBetween('door_stamp', array(DB::raw('DATEADD(hh, -13, GETDATE())'), DB::raw('GETDATE()')))->
            //     select('b_cust_id')->distinct()->get()->toArray();
            //     $data = $data->whereNotIn('b_cust_id',$outUser);
            // } else {
            $data = $data->where('door_type', $door_type);
            // }
        }
        return $data->count();
    }


    /**
     *  特定人員在指定日期存在紀錄
     * @param $id
     * @return int
     */
    protected function isDateExist($date,$b_factory_id,$b_factory_d_id = 0,$b_cust_id,$bc_type = 0)
    {
        if(!$date && !$b_cust_id) return 0;
        $data  = rept_doorinout_t::where('b_factory_id',$b_factory_id)->where('b_cust_id',$b_cust_id)->where('door_date',$date);
        if($bc_type)
        {
            $data = $data->where('bc_type',$bc_type);
        }
        if($b_factory_d_id)
        {
            $data = $data->where('b_factory_d_id',$b_factory_d_id);
        }
        $data = $data->first();
        return isset($data->id)? $data->id : 0;
    }

    /**
     * 取得工作許可證 在場人員
     * @param $work_id
     * @param int $door_type
     * @return array
     */
    protected function getWorkInMen($work_id,$door_type = 1)
    {
        $ret  = [];
        $data = rept_doorinout_t::where('wp_work_id',$work_id);

        if($door_type >= 0)
        {
            $data = $data->where('door_type',$door_type);
        }
        if($data->count())
        {
            $data = $data->get();
            foreach ($data as $val)
            {
                $ret[$val->b_cust_id] = $val->name;
            }
        }
        return $ret;
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getMenCount($date,$store = 0,$door = 0, $supply = 0,$door_type = 1)
    {
        if(!$date) return 0;
        $data  = rept_doorinout_t::where('door_date',$date)->where('door_type',$door_type);
        if(is_array($store) && count($store))
        {
            $data  = $data->whereIn('b_factory_id',$store);
        }elseif(is_numeric($store))
        {
            $data  = $data->where('b_factory_id',$store);
        }
        if($door)
        {
            $data  = $data->where('b_factory_d_id',$door);
        }
        if($supply)
        {
            $data = $data->where('b_supply_id',$supply);
        }
        return $data->count();
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getLocalMenCount($date,$door_id = 0,$door_type = 1)
    {
        if(!$date) return 0;
        $data  = rept_doorinout_t::where('door_date',$date)->where('door_type',$door_type);
        if($door_id)
        {
            $data  = $data->where('b_factory_d_id',$door_id);
        }
        return $data->count();
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getData($store_id,$user_id,$today = '',$door_type = -1)
    {
        if(!$today) $today = date('Y-m-d');
        if(!$user_id) return [];
        $data  = rept_doorinout_t::where('door_date',$today)->where('b_cust_id',$user_id);
        if($store_id)
        {
            $data  = $data->where('b_factory_id',$store_id);
        }
        if($door_type > 0)
        {
            $data = $data->where('door_type',$door_type);
        }
        return $data->first();
    }

    /**
     *
     * @param $id
     * @return int
     */
    protected function getUserStatus($b_cust_id)
    {
        $ret = '';
        if(!$b_cust_id) return $ret;
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $today = date('Y-m-d');
        $data  = rept_doorinout_t::join('b_factory_d as d','d.id','=','rept_doorinout_t.b_factory_d_id')->
        where('rept_doorinout_t.door_date',$today)->where('b_cust_id',$b_cust_id)->
        select('d.name as door','rept_doorinout_t.name','rept_doorinout_t.door_stamp','rept_doorinout_t.door_type')->first();

        if(isset($data->door))
        {
            $door_type = isset($doorTypeAry[$data->door_type])? $doorTypeAry[$data->door_type] : '異常進出';
            $ret = $data->name.'(從'.$data->door.'於'.substr($data->door_stamp,11,8).$door_type.')';
        }

        return $ret;
    }

    /**
     *  工單
     * @param $id
     * @return int
     */
    protected function getWorkId($store,$date,$b_cust_id,$door_stamp = '',$door_type = -1)
    {
        if(!$b_cust_id) return  0;
        $data  = rept_doorinout_t::where('door_date',$date)->where('b_cust_id',$b_cust_id);
        if($store)
        {
            $data = $data->where('b_factory_id',$store);
        }
        if($door_stamp)
        {
            $data = $data->where('door_stamp','>=',$door_stamp);
        }
        if($door_type > 0)
        {
            $data = $data->where('door_type',$door_type);
        }
        $data = $data->first();
        return isset($data->id)? $data->wp_work_id : 0;
    }

    /**
     *  特定人員進場時間
     * @param $id
     * @return int
     */
    protected function getInOutTime($rept_id)
    {
        if(!$rept_id) return  ['',''];
        $data  = rept_doorinout_t::where('id',$rept_id)->first();

        return isset($data->id)? [$data->door_type,$data->door_stamp] : ['',''];
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getLastData($b_cust_id,$store = 0, $date = '')
    {
        if(!$date) $date = date('Y-m-d');

        $data  = rept_doorinout_t::where('door_date',$date)->where('b_cust_id',$b_cust_id);
        if($store)
        {
            $data  = $data->where('b_factory_id',$store);
        }
        return $data->first();
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getUserInOutResult($user_id = 0,$local = 0,$date = '', $door_type = 1)
    {
        $ret = ['N',''];
        if(!$user_id) return $ret;
        if(!$date) $date = date('Y-m-d');

        $data  = rept_doorinout_t::where('door_date',$date)->where('door_type',$door_type);
        $data  = $data->where('b_cust_id',$user_id);
        if($local)
        {
            $data  = $data->where('b_factory_id',$local);
        }
        $data = $data->first();

        if(isset($data->id))
        {
            $ret = ['Y',$data->door_stamp];
        }

        return $ret;
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function isMenIn($user_id = [],$local = 0,$date = '',$work_id = [])
    {
        if(!is_array($user_id) || !count($user_id)) return 0;
        if(!$date) $date = date('Y-m-d');

        $data  = rept_doorinout_t::where('door_date',$date)->where('door_type',1);
        $data  = $data->whereIn('b_cust_id',$user_id);
        if(is_array($work_id) && count($work_id))
        {
            $data  = $data->whereIn('wp_work_id',$work_id);
        }
        if($local)
        {
            $data  = $data->where('b_factory_id',$local);
        }
        //dd([$user_id,$local,$date,$data->get()]);
        return $data->count();
    }

    /**
     *  該廠區之指定日期 人數[在廠/離場]
     * @param $id
     * @return int
     */
    protected function getLockWorkId($user_id,$b_factory_id = 0,$date = '' )
    {
        if(!$user_id) return [];
        if(!$date) $date = date('Y-m-d');

        $data  = rept_doorinout_t::join('wp_work as w','w.id','=','rept_doorinout_t.wp_work_id')->
                join('b_factory_d as d','d.id','=','rept_doorinout_t.b_factory_d_id')->
                where('rept_doorinout_t.door_date',$date)->where('rept_doorinout_t.b_cust_id',$user_id);
        if($b_factory_id)
        {
            $data  = $data->where('rept_doorinout_t.b_factory_id',$b_factory_id);
        }
        $data = $data->select('w.id','w.permit_no','d.name')->first();
        return $data;
    }

}
