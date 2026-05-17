<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternetPackage extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'internet_packages';

    protected $fillable = [
        'id', 'company_id', 'name', 'price', 'speed_down_kbps', 'speed_up_kbps',
        'quota_gb', 'billing_cycle', 'max_devices', 'is_unlimited',
        'fup_quota_down', 'fup_quota_up', 'fup_speed_down_kbps', 'fup_speed_up_kbps',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'speed_down_kbps' => 'decimal:2',
            'speed_up_kbps' => 'decimal:2',
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
