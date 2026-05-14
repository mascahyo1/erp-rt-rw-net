<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\MigrationServiceProvider::class,
    App\Providers\DuskServiceProvider::class,
];
