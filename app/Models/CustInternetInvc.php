<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustInternetInvc extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'cust_internet_invcs';

    protected $fillable = [
        'id', 'cust_internet_id', 'invoice_number', 'invoice_due_date',
        'amount', 'total_amount', 'discount_amount', 'tax_amount', 'grand_total',
        'due_date', 'paid_at', 'status', 'payment_status',
        'status_description', 'status_reason', 'description',
    ];

    protected function casts(): array
    {
        return [
            'invoice_due_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function custInternet(): BelongsTo
    {
        return $this->belongsTo(CustInternet::class);
    }
}
