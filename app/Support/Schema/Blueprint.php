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
    public function approvable(): void
    {
        $this->morphs('submitted_by');
        $this->text('submission_reason')->nullable();

        $this->enum('approval_status', [
            'pending',
            'approved',
            'rejected',
        ])->default('pending');

        $this->timestamp('approved_at')->nullable();
        $this->nullableMorphs('approved_by');
        $this->text('approval_reason')->nullable();
    }

    public function dropApprovable(): void
    {
        $this->dropMorphs('submitted_by');
        $this->dropMorphs('approved_by');

        $this->dropColumn([
            'submission_reason',
            'approval_status',
            'approved_at',
            'approval_reason',
        ]);
    }

    public function attachableFile()
    {
        $this->string('file_path');
        $this->string('file_name');
        $this->string('file_extension');
        $this->string('file_mime');
        $this->string('file_size');
        $this->string('file_description');
    }
}
