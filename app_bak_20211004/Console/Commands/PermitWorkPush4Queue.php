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
 * 檢查 工作許可證 已經收工，定期 通知廠商儘速離場
 */
class PermitWorkPush4Queue extends Command
{
    use WorkPermitWorkOrderTrait,PushTraits;
    // 命令名稱
    protected $signature = 'httc:permitworkpush4';

    // 說明文字
    protected $description = '[PermitWorkPush4] 針對已經收工之廠商通知儘速離場';

    public function __construct()
    {
        parent::__construct();
    }

    // Console 執行的程式
    public function handle()
    {
        $ret = [];
        $workAry = $this->getWorkPermitWorkOrderCheckRegular2();

        if(count($workAry))
        {
            foreach ($workAry as $val)
            {
                $work_id    = isset($val['id'])? $val['id'] : 0;
                $work_men   = isset($val['men'])? $val['men'] : '';
                $work_amt   = isset($val['amt'])? $val['amt'] : 0;
                if($work_id)
                {
                    //推播通知：承商儘速離廠
                    $ret[] = $this->pushToSupplyLeave1($work_id,$work_men);
                    //推播通知：通知轄區 廠商尚未離廠
                    $ret[] = $this->pushToSupplyLeave2($work_id,$work_amt,$work_men);
                }
            }
        }
        $workAry2 = $this->getWorkPermitWorkOrderCheckRegular3();

        if(count($workAry2))
        {
            foreach ($workAry2 as $val)
            {
                $work_id    = isset($val['id'])? $val['id'] : 0;
                $work_men   = isset($val['men'])? $val['men'] : '';
                $work_amt   = isset($val['amt'])? $val['amt'] : 0;
                if($work_id)
                {
                    //推播通知：通知監造　有人暫停後沒有工作
                    $ret[] = $this->pushToSupplyLeave3($work_id,$work_amt,$work_men);
                }
            }
        }
        $workAry3 = $this->getApiWorkPermitInReWorkProcess();

        if(count($workAry3))
        {
            foreach ($workAry3 as $work_id)
            {
                //推播通知：通知承攬商／監造／轄區　工單暫停
                $ret[] = $this->pushToHowManyInReWork($work_id);
            }
        }
        //2. Log
        LogLib::putCronLog('PermitWorkPush4',count($workAry),json_encode($ret));
    }
}
