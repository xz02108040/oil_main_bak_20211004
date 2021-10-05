<?php

namespace App\Console\Commands;

use App\Http\Traits\Push\PushTraits;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class PermitWorkPush2Queue
 * 檢查 工作許可證有多少張沒有啟動
 */
class PermitWorkPush2Queue extends Command
{
    use PushTraits;
    // 命令名稱
    protected $signature = 'httc:permitworkpush2';

    // 說明文字
    protected $description = '[PermitWorkPush2] 工作許可證有多少張沒有審查';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        //1. 查出 至今尚未啟動的工作許可證
        list($ret,$reply) = $this->pushToHowManyRPPermitReady();
        //2. Log
        LogLib::putCronLog('PermitWorkPush2',$ret,$reply);
    }
}
