<?php

namespace App\Console\Commands;

use App\Http\Traits\Engineering\EngineeringHistoryTrait;
use App\Http\Traits\Engineering\EngineeringTrait;
use App\Http\Traits\Push\PushTraits;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class ProjectOverDateQueue
 * 檢查 工程案件過期
 */
class ProjectOverDateQueue extends Command
{
    use EngineeringTrait,EngineeringHistoryTrait,PushTraits;
    // 命令名稱
    protected $signature = 'httc:projectoverdate';

    // 說明文字
    protected $description = '[ProjectOverDate] 將工程案件之過期作廢';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        list($ret,$reply) = $this->checkProjectOverDate();

        //2. Log
        LogLib::putCronLog('ProjectOverDate',$ret,$reply);
    }
}
