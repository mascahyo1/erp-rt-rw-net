<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustInternet extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'cust_internets';

    protected $fillable = [
        'id',
        'customer_id',
        'internet_package_id',
        'account_number',
        'router_sn',
        'customer_address',
        'customer_address_long',
        'customer_address_lat',
        'usage_upload_kb',
        'usage_download_kb',
        'internet_status',
        'company_notes',
    ];

    protected function casts(): array
    {
        return [
            'usage_upload_kb' => 'decimal:2',
            'usage_download_kb' => 'decimal:2',
            'customer_address_lat' => 'decimal:7',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function internetPackage(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }

    public function invoices()
    {
        return $this->hasMany(CustInternetInvc::class);
    }
}
