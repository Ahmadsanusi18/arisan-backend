<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        /*
        * Cek reminder setiap menit.
        *
        * Command sendiri yang menentukan apakah
        * waktunya sudah sesuai dengan setting admin.
        */
        $schedule->command('arisan:kirim-reminder')
            ->everyMinute()
            ->withoutOverlapping();

        /*
        * Kirim notifikasi kloter penuh dan pemenang harian.
        */
        $schedule->command('arisan:kirim-notifikasi-pemenang')
            ->everyMinute()
            ->withoutOverlapping();

        /*
        * Kirim daftar telat bayar.
        */
        $schedule->command('arisan:kirim-list-telat')
            ->dailyAt('00:01');

        $schedule->command('arisan:kirim-list-telat')
            ->dailyAt('09:00');
}

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}