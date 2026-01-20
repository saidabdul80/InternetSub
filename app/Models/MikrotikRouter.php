<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikrotikRouter extends Model
{
    /** @use HasFactory<\Database\Factories\MikrotikRouterFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'use_ssl',
        'timeout',
        'is_active',
    ];

    /**
     * Get the vouchers associated with the router.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'use_ssl' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
