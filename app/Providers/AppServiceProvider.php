<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Atur locale Carbon ke Bahasa Indonesia
        \Carbon\Carbon::setLocale('id');

        // Paksa semua URL (asset, route) memakai https di server produksi
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\View::composer(['layouts.app', 'guru.dashboard', 'jurnal.index'], function ($view) {
            // Dihitung sekali per permintaan (layout + halaman memakai data yang sama)
            static $overdue = null;
            if ($overdue === null) {
                $berhak = in_array(session('id_role'), [2, 3]) || \App\Services\JadwalHariIni::bkMengajar();
                $overdue = $berhak ? \App\Services\JurnalWarningService::getOverdueJadwal() : collect([]);
            }
            $view->with('overdueJadwals', $overdue);
        });
    }
}
