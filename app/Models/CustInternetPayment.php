<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustInternetPayment extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'cust_internet_payments';

    protected $fillable = [
        'id', 'cust_internet_invc_id', 'amount_paid', 'payment_date',
        'payment_method', 'status', 'proof_file', 'provider',
        'status_description', 'status_reason', 'data',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'amount_paid' => 'decimal:2',
            'data' => 'array',
        ];
    }

    public function custInternetInvc(): BelongsTo
    {
        return $this->belongsTo(CustInternetInvc::class, 'cust_internet_invc_id');
    }
}
