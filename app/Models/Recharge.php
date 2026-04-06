<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'username',
        'plan_id',
        'plan_name',
        'recharged_at',
        'expires_at',
        'status',
        'method',
        'router_id',
        'router_name',
        'service_type',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'recharged_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
