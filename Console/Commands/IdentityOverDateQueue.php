<?php

namespace App\Console\Commands;

use App\Http\Traits\Supply\SupplyMemberIdentityTrait;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class IdentityOverDateQueue
 * 檢查 工單過期
 */
class IdentityOverDateQueue extends Command
{
    use SupplyMemberIdentityTrait;
    // 命令名稱
    protected $signature = 'httc:identityoverdate';

    // 說明文字
    protected $description = '[IdentityOverDate] 將工單之過期作廢';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        list($ret,$reply) = $this->checkSupplyMemberIdentityOverDate();

        //2. Log
        LogLib::putCronLog('IdentityOverDate',$ret,$reply);
    }
}
