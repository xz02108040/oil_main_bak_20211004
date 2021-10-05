<?php

namespace App\Console\Commands;

use App\Http\Traits\Factory\DoorTrait;
use App\Http\Traits\Report\ReptDoorInOutDailyTrait;
use App\Lib\FcmPusherLib;
use App\Lib\LogLib;
use App\Lib\SHCSLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class DoorInoutQueue
 * 承攬商進出累計工時表 產生器
 */
class DoorInoutQueue extends Command
{
    use ReptDoorInOutDailyTrait;
    // 命令名稱
    protected $signature = 'httc:doorinoutrept';

    // 說明文字
    protected $description = '[DoorInoutQueue] 承攬商進出累計工時表';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        $today = SHCSLib::addDay(-1);
        list($ret,$reply) = $this->genDoorInOutTimes(1,$today);
        list($ret,$reply) = $this->genDoorInOutDaily(1,$today);

        //2. Log
        LogLib::putCronLog('ProjectPass',$ret,implode(',',$reply));
    }
}
