<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpIncentive extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'emp_incentives';

    protected $fillable = [
        'id', 'company_id', 'code', 'name', 'type', 'value',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function logs() { return $this->hasMany(EmpIncentiveLog::class); }
}
