<?php

namespace App\Providers;

use App\Support\Payment\MidtransGateway;
use App\Support\Payment\PaymentManager;
use App\Support\Session\MultiAuthDatabaseSessionHandler;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, function () {
            $manager = new PaymentManager;
            $manager->register('midtrans', new MidtransGateway);
            // Register gateway lain di sini: xendit, stripe, dsb.
            return $manager;
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Session::extend('multi-auth-database', function ($app) {
            $connection = $app['db']->connection($app['config']['session.connection']);
            $table = $app['config']['session.table'];
            $minutes = $app['config']['session.lifetime'];

            return new MultiAuthDatabaseSessionHandler($connection, $table, $minutes, $app);
        });
    }
}
