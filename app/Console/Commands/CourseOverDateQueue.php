<?php

namespace App\Console\Commands;

use App\Http\Traits\Engineering\TraningMemberTrait;
use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class CourseOverDateQueue
 * 檢查 教育訓練 過期
 */
class CourseOverDateQueue extends Command
{
    use TraningMemberTrait;
    // 命令名稱
    protected $signature = 'httc:courseoverdate';

    // 說明文字
    protected $description = '[CourseOverDate] 將教育訓練之過期作廢';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        list($ret,$reply) = $this->checkTraningMemberOverDate();

        //2. Log
        LogLib::putCronLog('CourseOverDate',$ret,$reply);
    }
}
