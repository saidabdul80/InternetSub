<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice',
        'username',
        'customer_id',
        'plan_name',
        'price',
        'recharged_at',
        'expires_at',
        'method',
        'router_id',
        'router_name',
        'service_type',
        'note',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'recharged_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(NetworkRouter::class, 'router_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
