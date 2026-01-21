<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    /** @use HasFactory<\Database\Factories\VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'plan_type',
        'status',
        'payment_id',
        'reserved_at',
        'used_at',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_type', 'plan_type');
    }

    protected function casts(): array
    {
        return [
            'plan_type' => 'integer',
            'reserved_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
