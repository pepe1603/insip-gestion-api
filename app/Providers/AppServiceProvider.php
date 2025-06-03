<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
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
        //
        if (config('app.debug')) {
            DB::listen(function ($query) {
                logger()->info("SQL: " . $query->sql);
                logger()->info("Bindings: " . json_encode($query->bindings));
                logger()->info("Time: " . $query->time);
            });
        }
    }
}
