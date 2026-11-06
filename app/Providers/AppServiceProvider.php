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

        \Illuminate\Support\Facades\View::composer(['layouts.app', 'guru.dashboard', 'jurnal.index'], function ($view) {
            if (in_array(session('id_role'), [2, 3])) {
                $view->with('overdueJadwals', \App\Services\JurnalWarningService::getOverdueJadwal());
            } else {
                $view->with('overdueJadwals', collect([]));
            }
        });
    }
}
