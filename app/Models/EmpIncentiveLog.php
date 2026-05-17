<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpIncentiveLog extends Model
{
    use HasBlameable, HasSoftDelete;

    protected $table = 'emp_incentive_logs';

    protected $fillable = [
        'emp_incentive_id', 'cust_internet_invcs_id', 'amount', 'date',
        'approval_status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function empIncentive(): BelongsTo { return $this->belongsTo(EmpIncentive::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(CustInternetInvc::class, 'cust_internet_invcs_id'); }
}
