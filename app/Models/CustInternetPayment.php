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
        'id', 'cust_internet_id', 'amount', 'payment_date',
        'payment_method', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function custInternet(): BelongsTo
    {
        return $this->belongsTo(CustInternet::class);
    }
}
