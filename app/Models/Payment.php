<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'plan_type',
        'reference',
        'amount',
        'currency',
        'access_point',
        'callback_url',
        'status',
        'paystack_reference',
        'paid_at',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    protected function casts(): array
    {
        return [
            'plan_type' => 'integer',
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
