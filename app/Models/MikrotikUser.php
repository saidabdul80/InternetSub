<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikUser extends Model
{
    /** @use HasFactory<\Database\Factories\MikrotikUserFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'username',
        'profile',
        'plan_type',
        'status',
        'payment_id',
        'activated_at',
        'expires_at',
        'last_synced_at',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'plan_type' => 'integer',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
