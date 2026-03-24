<?php

namespace App\Providers;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Sheets::class, function () {
            $client = new GoogleClient;
            $client->setAuthConfig(config('google_sheets.credentials_path'));
            $client->addScope(Sheets::SPREADSHEETS);

            return new Sheets($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
