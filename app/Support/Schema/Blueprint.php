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

    public function confirmable(): void
    {
        $this->morphs('submitted_by');
        $this->enum('confirmation_status', ['pending', 'approved', 'rejected']);
        $this->timestamp('confirmed_at')->nullable();
        $this->nullableMorphs('confirmed_by');
        $this->text('confirmation_reason')->nullable();
    }

    public function dropConfirmable(): void
    {
        $this->dropMorphs('submitted_by');
        $this->dropColumn('confirmed_at', 'status_confirmation', 'confirmation_reason');
        $this->dropMorphs('confirmed_by');
    }
}
