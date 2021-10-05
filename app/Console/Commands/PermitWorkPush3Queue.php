<?php

namespace App\Console\Commands;

use App\Http\Traits\Push\PushTraits;
use App\Http\Traits\WorkPermit\WorkPermitWorkOrderTrait;
use App\Lib\LogLib;
use App\Model\push_queue;
use Illuminate\Console\Command;
use Storage;
use DB;

/**
 * Class PushToWorkPermit1Queue
 * 檢查 工作許可證 正在施工中 定期氣體推播
 */
class PermitWorkPush3Queue extends Command
{
    use WorkPermitWorkOrderTrait,PushTraits;
    // 命令名稱
    protected $signature = 'httc:permitworkpush3';

    // 說明文字
    protected $description = '[PermitWorkPush3] 氣體偵測，承攬商提醒，每小時一次';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        $ret = [];
        $workAry = $this->getWorkPermitWorkOrderCheckRegular();

        if(count($workAry))
        {
            foreach ($workAry as $val)
            {
                $work_id = isset($val['id'])? $val['id'] : 0;
                if($work_id)
                {
                    //推播通知
                    $ret[] = $this->pushToRegular1($work_id);
                }
            }
        }
        //2. Log
        LogLib::putCronLog('PermitWorkPush3',count($workAry),json_encode($ret));
    }
}
