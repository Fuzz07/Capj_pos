<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // The UI is built on Bootstrap 5; without this Laravel renders its
        // Tailwind pagination markup, which styles badly here.
        Paginator::useBootstrapFive();
    }
}
