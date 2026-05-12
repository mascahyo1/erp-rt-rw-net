<?php

namespace App\Providers;

use App\Support\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Illuminate\Database\Schema\Blueprint::class,
            Blueprint::class
        );
    }
}
