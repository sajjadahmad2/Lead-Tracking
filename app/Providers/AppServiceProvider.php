<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Schema;

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
        if (strpos(url()->full(), '127.0.0.1') !== false || strpos(url()->full(), 'localhost') !== false) {
            \URL::forceScheme('http');
        } else {
            \URL::forceScheme('https');
        }
        \Validator::extend('password', 'App\Rules\PasswordValidation@passes');
        Schema::defaultStringLength(191);
    }
}
