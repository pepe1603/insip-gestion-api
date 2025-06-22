<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Observers\EmpleadoObserver;
use App\Models\Empleado;

class AppServiceProvider extends ServiceProvider
{


    /**
     * the model observed by the service provider.
     * @var string
     * This property is not used in this example, but it can be useful for
     * tracking which model the service provider is associated with.
     */

     protected $observers = [
        Empleado::class => [
            EmpleadoObserver::class,
        ],
        ];

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
