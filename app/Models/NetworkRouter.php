<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkRouter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'username',
        'password',
        'description',
        'coordinates',
        'status',
        'last_seen_at',
        'coverage',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class, 'router_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'router_id');
    }
}
