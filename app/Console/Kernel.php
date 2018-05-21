<?php

namespace App\Console;

use App\Console\Commands\GenerateDailyStatement;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GenerateDailyStatement::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //生成每日数据报表
        $schedule->command('admin:generate-daily-statement')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(config('custom.cron_task_log'));
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
