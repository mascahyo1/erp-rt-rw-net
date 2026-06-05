<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustInternetInvc extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'cust_internet_invcs';

    protected $fillable = [
        'id', 'cust_internet_id', 'invoice_number',
        'usage_start_date', 'usage_end_date',
        'amount', 'total_amount', 'discount_amount', 'tax_amount', 'grand_total',
        'due_date', 'paid_at', 'status', 'payment_status',
        'status_description', 'status_reason', 'description',
        'restored_at', 'restored_by_type', 'restored_by_id',
    ];

    protected function casts(): array
    {
        return [
            'usage_start_date' => 'date',
            'usage_end_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'restored_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(CustInternetPayment::class, 'cust_internet_invc_id');
    }

    /**
     * Hanya payment yang sudah disetujui admin (status='paid').
     * Dipakai untuk hitung total_paid & tampil di Riwayat Pembayaran.
     */
    public function approvedPayments(): HasMany
    {
        return $this->payments()->where('status', 'paid')->orderBy('payment_date', 'asc');
    }

    /**
     * Total nominal yang sudah dibayar (hanya payment approved/paid).
     * Computed real-time dari approved payments, bukan dari kolom payment_status.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->approvedPayments()->sum('amount_paid');
    }

    /**
     * Sisa tagihan yang belum dibayar (grand_total - total_paid, minimum 0).
     */
    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->grand_total - $this->total_paid);
    }

    /**
     * Label status bayar hasil komputasi:
     *  - 'paid'    : total_paid >= grand_total (Lunas)
     *  - 'partial' : 0 < total_paid < grand_total (Sebagian)
     *  - 'unpaid'  : total_paid == 0 (Belum Bayar)
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $paid = $this->total_paid;
        $total = (float) $this->grand_total;
        if ($paid <= 0) return 'unpaid';
        if ($paid + 0.01 >= $total) return 'paid';
        return 'partial';
    }
}

