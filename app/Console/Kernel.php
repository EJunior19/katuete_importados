<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ProcessCreditNotifications; // ← importa el Job
use App\Jobs\UpcomingCreditReminders; // ← importa el Job

class Kernel extends ConsoleKernel
{
    /**
     * Define el scheduler de Laravel.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecuta el job todos los días a las 08:00
        $schedule->job(new ProcessCreditNotifications())
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->description('Verifica créditos vencidos y envía alertas automáticas');
    }

    /**
     * Registra comandos artisan (app/Console/Commands) y routes/console.php.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    /**
     * Registra middlewares para comandos artisan.
     */
    protected $middlewareAliases = [
    // ...
    'files' => \App\Http\Middleware\NoopMiddleware::class,

];


}
