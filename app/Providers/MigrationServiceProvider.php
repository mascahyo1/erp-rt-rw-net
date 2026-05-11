<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blueprint::macro('blameable', function () {
            /** @var Blueprint $this */
            $this->nullableMorphs('created_by');
            $this->nullableMorphs('updated_by');
        });

        Blueprint::macro('dropBlameable', function () {
            /** @var Blueprint $this */
            $this->dropMorphs('created_by');
            $this->dropMorphs('updated_by');
        });

        Blueprint::macro('softDelete', function () {
            /** @var Blueprint $this */
            $this->timestamp('deleted_at')->nullable();
            $this->nullableMorphs('deleted_by');
            $this->timestamp('restored_at')->nullable();
            $this->nullableMorphs('restored_by');
        });

        Blueprint::macro('dropSoftDelete', function () {
            /** @var Blueprint $this */
            $this->dropMorphs('deleted_by');
            $this->dropMorphs('restored_by');
            $this->dropColumn(['deleted_at', 'restored_at']);
        });
    }

    public function register(): void
    {
    }
}
