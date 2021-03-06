<?php

namespace App\Console;

use App\Console\Commands\SyncAttendanceRecordsCommand;
use App\Console\Commands\SyncTimeRecorderLogsCommand;
use App\Console\Commands\SyncEmployeesCommand;
use App\Console\Commands\SyncAttendanceRequestsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncAttendanceRecordsCommand::class,
        SyncAttendanceRequestsCommand::class,
        SyncEmployeesCommand::class,
        SyncTimeRecorderLogsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
