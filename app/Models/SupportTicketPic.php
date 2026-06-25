<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot Gangguan ↔ Employee. Tiap record = 1 PIC untuk 1 tiket.
 * Field `is_main_pic` = true untuk PIC utama (tampil di datatable).
 *
 * Usage:
 *   $gangguan->pics                      // all PICs (withTrashed by default di Eloquent)
 *   $gangguan->mainPic                   // PIC utama (or null)
 *   $gangguan->additionalPics            // PIC tambahan (is_main_pic = false)
 *   SupportTicketPic::mainScope()        // scope: where is_main_pic = true
 */
class SupportTicketPic extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'support_ticket_pics';

    protected $fillable = [
        'id',
        'support_ticket_id',
        'employee_id',
        'is_main_pic',
    ];

    protected function casts(): array
    {
        return [
            'is_main_pic' => 'boolean',
        ];
    }

    public function gangguan(): BelongsTo
    {
        return $this->belongsTo(Gangguan::class, 'support_ticket_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeMainScope($query)
    {
        return $query->where('is_main_pic', true);
    }
}
