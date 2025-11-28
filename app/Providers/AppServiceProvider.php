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
        // Registrar el observer para HabitacionReserva
        \App\Models\HabitacionReserva::observe(\App\Observers\HabitacionReservaObserver::class);
    }
}
