<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'photo',
        'pppoe_username',
        'pppoe_password',
        'pppoe_ip',
        'full_name',
        'address',
        'city',
        'district',
        'state',
        'zip',
        'phone_number',
        'email',
        'coordinates',
        'account_type',
        'balance',
        'service_type',
        'auto_renewal',
        'status',
        'created_by',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'auto_renewal' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomerCustomField::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
