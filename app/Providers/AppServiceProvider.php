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
            $client->setAuthConfig([
                'type' => 'service_account',
                'client_id' => '',
                'client_email' => config('google_sheets.service_account_email'),
                'private_key' => config('google_sheets.service_account_private_key'),
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ]);
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
