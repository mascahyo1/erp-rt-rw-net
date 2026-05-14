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
        'amount', 'status', 'status_description', 'status_reason',
    ];

    protected function casts(): array
    {
        return [
            'invoice_due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function custInternet(): BelongsTo
    {
        return $this->belongsTo(CustInternet::class);
    }
}
