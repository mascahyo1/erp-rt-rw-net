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
        $this->string('submission_proof_file')->nullable();
        $this->enum('review_status', [
            'pending',
            'approved',
            'rejected',
        ])->default('pending');

        $this->timestamp('reviewed_at')->nullable();
        $this->nullableMorphs('reviewed_by');
        $this->timestamp('review_attachment')->nullable();
        $this->text('review_reason')->nullable();
    }

    public function dropApprovable(): void
    {
        $this->dropMorphs('submitted_by');
        $this->dropMorphs('reviewed_by');

        $this->dropColumn([
            'submission_reason',
            'approval_status',
            'reviewed_at',
            'review_reason',
        ]);
    }
}
