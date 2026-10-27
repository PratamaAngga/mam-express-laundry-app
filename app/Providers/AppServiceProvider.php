<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\Gateway;
use App\Services\WhatsApp\LogGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Driver WhatsApp dipilih dari WHATSAPP_DRIVER di .env (bawaan: log = tidak mengirim)
        $this->app->bind(Gateway::class, fn () => match (config('whatsapp.driver')) {
            'fonnte' => new FonnteGateway(),
            default => new LogGateway(),
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
