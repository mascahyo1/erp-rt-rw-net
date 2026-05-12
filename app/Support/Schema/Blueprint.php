<?php

namespace App\Support\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint
{
    public function blameable(): void
    {
        $this->nullableMorphs('created_by');
        $this->nullableMorphs('updated_by');
    }

    public function dropBlameable(): void
    {
        $this->dropMorphs('created_by');
        $this->dropMorphs('updated_by');
    }

    public function softDelete(): void
    {
        $this->timestamp('deleted_at')->nullable();
        $this->nullableMorphs('deleted_by');
        $this->timestamp('restored_at')->nullable();
        $this->nullableMorphs('restored_by');
    }

    public function dropSoftDelete(): void
    {
        $this->dropMorphs('deleted_by');
        $this->dropMorphs('restored_by');
        $this->dropColumn(['deleted_at', 'restored_at']);
    }
}
