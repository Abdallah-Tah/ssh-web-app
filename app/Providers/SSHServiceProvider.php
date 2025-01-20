<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SSHService;
use App\Services\SFTPService;
use App\Services\ServerMonitoringService;

class SSHServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SSHService::class, function ($app) {
            return new SSHService();
        });

        $this->app->singleton(SFTPService::class, function ($app) {
            return new SFTPService();
        });

        $this->app->singleton(ServerMonitoringService::class, function ($app) {
            return new ServerMonitoringService($app->make(SSHService::class));
        });
    }
}
