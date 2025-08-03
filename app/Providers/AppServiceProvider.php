<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set default timezone untuk seluruh aplikasi
        date_default_timezone_set(config('app.timezone'));
        
        // Set Carbon default timezone
        \Carbon\Carbon::setLocale('id');
    }
}
