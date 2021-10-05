<?php

namespace App\Console\Commands;

use App\Lib\FcmPusherLib;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

class PushQueue extends Command
{
    // 命令名稱
    protected $signature = 'shcs:PushQueue';

    // 說明文字
    protected $description = '[Push] 定期推播';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        $pushCnt    = 0;
        $queueList = push_queue::getNedPushList(); //取得需要推播的訊息

        if($needPushCnt = count($queueList))
        {
            foreach ($queueList as $val)
            {
                $target_cust    = $val->target_cust;
                $token          = $val->push_token;
                $title          = $val->title;
                $cont           = $val->cont;

                if($target_cust && $token && $title && $cont)
                {
                    $ret = FcmPusherLib::pushSingleDevice($target_cust,$token,9,$title,$cont);
                    if($ret)
                    {
                        $data = push_queue::find($val->id);
                        $data->isPush = 'Y';
                        $data->puser_time = date('Y-m-d H:i:s');
                        $data->save();
                        $pushCnt++;
                    }
                }
            }
        }

        $ret = 'queue_num:'.$needPushCnt.';push_suc_num:'.$pushCnt;
        //2. Log
        LogLib::putCronLog('PushQueue',$ret);
    }
}