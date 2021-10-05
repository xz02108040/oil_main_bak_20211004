<?php

namespace App\Console\Commands;

use App\Http\Traits\Factory\DoorTrait;
use App\Lib\FcmPusherLib;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class CoursePassQueue
 * 教育訓練資格通過 產生器
 */
class CoursePassQueue extends Command
{
    use DoorTrait;
    // 命令名稱
    protected $signature = 'httc:coursepass';

    // 說明文字
    protected $description = '[CoursePass] 教育訓練資格通過';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        list($ret,$reply) = $this->createCoursePass();

        //2. Log
        LogLib::putCronLog('CoursePass',$ret,$reply);
    }
}
