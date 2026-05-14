<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DuskServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    public function register(): void
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->singleton(
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \App\Http\Middleware\DisableCsrfForDusk::class
            );
        }
    }
}
