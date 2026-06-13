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
        'payment_method', 'provider', 'code', 'status', 'proof_file',
        'status_description', 'status_reason', 'review_attachment', 'data',
        'snap_token', 'midtrans_order_id', 'midtrans_payment_type',
        'midtrans_va_number', 'midtrans_fraud_status',
        'midtrans_settled_at', 'midtrans_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'amount_paid' => 'decimal:2',
            'data' => 'array',
            'restored_at' => 'datetime',
            'midtrans_settled_at' => 'datetime',
            'midtrans_expires_at' => 'datetime',
        ];
    }

    /**
     * Apakah payment ini via Midtrans gateway?
     */
    public function isMidtrans(): bool
    {
        return $this->provider === \App\Enums\PaymentProvider::MIDTRANS->value;
    }

    /**
     * Apakah payment ini masih bisa di-retry (pending & bukan expired)?
     */
    public function canRetry(): bool
    {
        return $this->isMidtrans() && $this->status === 'pending';
    }

    public function custInternetInvc(): BelongsTo
    {
        return $this->belongsTo(CustInternetInvc::class, 'cust_internet_invc_id');
    }
}
