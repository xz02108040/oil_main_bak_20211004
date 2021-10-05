<?php

namespace App\Http\Traits\Push;

use App\Lib\FcmPusherLib;
use App\Lib\HtmlLib;
use App\Lib\SHCSLib;
use App\Lib\TableLib;
use App\Model\Factory\b_factory;
use App\Model\Factory\b_factory_a;
use App\Model\push_queue;
use App\Model\Report\rept_doorinout_t;
use App\Model\sys_param;
use App\Model\User;
use App\Model\View\view_dept_member;
use App\Model\View\view_door_supply_member;
use App\Model\View\view_log_door_today;
use App\Model\View\view_user;
use App\Model\WorkPermit\wp_work;
use Storage;
use DB;
use Lang;

/**
 * 推播：群組推播=[部門][承商][工作許可證]
 *
 */
trait PushGroupTrait
{
    /**
     * 事件：ＴＥＳＴ推播
     */
    protected function pushToGroupTest()
    {
        $ret  = 0;
        $data = User::where('pusher_id','!=','')->select('id','name','pusher_id');

        if($data->count())
        {
            foreach ($data->get() as $val)
            {
                if(strlen($val->pusher_id) > 10)
                {
                    //所需參數
                    $push_type  = 1;
                    $name       = $val->name;
                    $login_time = date('Y-m-d H:i:s');
                    //組合 推播訊息
                    $title = Lang::get('sys_push.P100000_T',['time'=>date('Y-m-d H:i:s')]);
                    $cont  = Lang::get('sys_push.P100000_C',['name1'=>$name,'name2'=>$login_time]);
                    if(FcmPusherLib::pushSingleDevice($val->id,$val->pusher_id,$push_type,$title,$cont,1))
                    {
                        $ret++;
                    }
                }
            }
        }

        return $ret;
    }
}
