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
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('contracts:update')->hourly();
        $schedule
            ->command('load-player-info-in-cache')
            ->weeklyOn(1, '9:00')
            ->timezone('America/Chicago')
        ;

        $schedule
            ->command('load-player-info-in-cache')
            ->weeklyOn(5, '9:00')
            ->timezone('America/Chicago')
        ;

        $schedule
            ->command('update-interactions')
            ->everyFifteenMinutes()
        ;

        $schedule
            ->command('send-rocket-notifications')
            ->everyFifteenMinutes()
        ;
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
