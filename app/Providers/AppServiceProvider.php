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
        // Never serialize the decrypted password into artisan config:cache output.
        if (! $this->app->runningInConsole() && ($password = app(\App\Services\SmtpCredentialStore::class)->password())) {
            config(['mail.mailers.smtp.password'=>$password]);
        }
    }
}
