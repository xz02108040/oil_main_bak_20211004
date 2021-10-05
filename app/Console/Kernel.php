<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    // 定義應用程式的 Artisan 指令
    protected $commands = [
        Commands\CoursePassQueue::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //1. 產生 教育訓練資格 通過<每30分鐘檢查>
        //$schedule->command('httc:coursepass')->hourlyAt('15')->between('5:00', '23:00')->withoutOverlapping();
        //2. 產生 教育訓練 過期<每天 00:10>
        //$schedule->command('httc:courseoverdate')->dailyAt('00:10')->withoutOverlapping();
        //3. 產生 工程身份 過期<每天 00:15>
        //$schedule->command('httc:identityoverdate')->dailyAt('00:15')->withoutOverlapping();
        //4. 產生 工程案件 過期<每天 00:20>
        //$schedule->command('httc:projectoverdate')->dailyAt('00:20')->withoutOverlapping();


        //100. 通知 當日工作許可證尚未審查 <每天 15:00>
        //$schedule->command('httc:permitworkpush1')->dailyAt('15:00')->withoutOverlapping();
        //101. 通知 當日工作許可證尚未啟動 <每天 10:00>
        //$schedule->command('httc:permitworkpush2')->dailyAt('10:00')->withoutOverlapping();
        //102. 通知 當日工作許可證定期氣體偵測 <每天 一個小時>
        $schedule->command('httc:permitworkpush3')->hourly()->between('00:00', '23:00')->unlessBetween('12:00','13:00')->unlessBetween('17:00','18:00')->withoutOverlapping();
        //104. 通知 當日工作許可證定期離場通知 <每天 一個小時>
        $schedule->command('httc:permitworkpush4')->hourly()->between('00:00', '23:00')->unlessBetween('12:00','13:00')->unlessBetween('17:00','18:00')->withoutOverlapping();
        
        // 修正門禁進出紀錄資料
        $schedule->command('httc:fixlogdoorinout')->hourlyAt('0')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
