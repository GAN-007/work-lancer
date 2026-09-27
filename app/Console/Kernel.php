<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('galika:run --limit=50')->everyMinute()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:inbound')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:wealth --limit=20')->everyFiveMinutes()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:active --limit=50')->everyMinute()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:worker --limit=50')->everyMinute()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:outbox --limit=100')->everyMinute()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:watchdog')->everyMinute()->withoutOverlapping(5)->onOneServer()->runInBackground();
        $schedule->command('galika:reconcile-acks')->everyMinute()->withoutOverlapping(10)->onOneServer()->runInBackground();
        $schedule->command('galika:scorecard')->sundays()->at('20:00')->withoutOverlapping(30)->onOneServer();
        $schedule->command('galika:backup-check')->hourly()->withoutOverlapping(10)->onOneServer();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
