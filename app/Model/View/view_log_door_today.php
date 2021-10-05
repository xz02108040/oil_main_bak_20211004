<?php

namespace App\Model\View;

use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Model\Report\rept_doorinout_t;
use Illuminate\Database\Eloquent\Model;
use Lang;

class view_log_door_today extends Model
{
    /**
     * 使用者Table: 列出當日 門禁進出資料
     */
    protected $table = 'view_log_door_today';
    /**
     * Table Index:
     */
    protected $primaryKey = 'log_id';

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

    protected $guarded = ['unit_id'];

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isExist($store_id,$uid,$door_type = -1,$work_id = 0)
    {
        $isOk = 0;
        if(!$store_id || !$uid) return $isOk;
        $data  = view_log_door_today::selectRaw('log_id')->
        where('b_factory_id',$store_id)->where('unit_id',$uid)->orderby('door_stamp','desc')->where('door_result','Y')->first();

        if(isset($data->log_id))
        {
            $isOk = 1;
            $data = view_log_door_today::find($data->log_id);
            if($door_type > 0 && $door_type != $data->door_type)
            {
                $isOk = 0;
            }
            if($work_id > 0 && $work_id != $data->wp_work_id)
            {
                $isOk = 0;
            }
        }

        return $isOk;
    }

    /**
     *  是否存在
     * @param $id
     * @return int
     */
    protected function isIn($uid,$isWorkId = 0)
    {
        $data =  view_log_door_today::where('unit_id',$uid)->where('door_result','Y')->orderby('log_id','desc');
        if($isWorkId)
        {
            $data = $data->whereIn('wp_work_id',[0,$isWorkId]);
        }
        $data = $data->first();
        return (isset($data->door_type) && $data->door_type == 1) ? true : false;
    }

    /**
     *  個人當日進出紀錄資訊
     * @param $id
     * @return int
     */
    protected function getMenLog($id)
    {
        if(!$id) return '';
        $Icon = HtmlLib::genIcon('caret-square-o-right');
        $data  = view_log_door_today::where('log_id',$id)->first();
        $doorTypeAry    = SHCSLib::getCode('DOOR_INOUT_TYPE2');
        $door_type = (isset($data->door_type) && isset($doorTypeAry[$data->door_type]))? $doorTypeAry[$data->door_type] : '';

        return isset($data->img_path)? $data->unit_name.$Icon.$data->name.$Icon.$door_type.$Icon.$data->door_stamp : '';
    }

    /**
     *  頭像
     * @param $id
     * @return int
     */
    protected function getImg($id)
    {
        if(!$id) return '';
        $data  = view_log_door_today::find($id);
        return isset($data->img_path)? $data->img_path : '';
    }

    /**
     * 取得使用者入廠人數
     * @param $userArr 判斷的使用者清單
     * @param $project_id 案件 ID
     * @param $factory_id 廠區 ID
     * @param $factory_d_id 門別 ID
     * @param $door_stamp 判斷的刷卡時間
     */
    protected function getUserInArray($userArr, $project_id, $factory_id = 0, $factory_d_id = 0, $door_stamp = '')
    {
        if (empty($door_stamp)) $door_stamp = date('Y-m-d');
        $userInArr = [];
        $query = view_log_door_today::whereIn('unit_id', $userArr)
            ->where('e_project_id', $project_id)
            ->where('door_result', 'Y')
            ->where('door_stamp', '<', $door_stamp) // 排除超過刷卡時間的資料，避免誤判
            ->orderBy('door_stamp', 'asc');
        if (!empty($factory_id)) {
            $query->where('b_factory_id', $factory_id);
        }
        if (!empty($factory_d_id)) {
            $query->where('b_factory_d_id', $factory_d_id);
        }
        $userLogArr = $query->get();
        if ($userLogArr) { // 紀錄在廠內的使用者 b_cust_id 清單
            foreach ($userLogArr as $userLog) {
                if ($userLog->door_type == 1) { // 進廠
                    $userInArr[$userLog->unit_id] = $userLog->door_stamp;
                }
                if ($userLog->door_type == 2) { // 出廠
                    unset($userInArr[$userLog->unit_id]);
                }
            }
        }
        return $userInArr;
    }

}
