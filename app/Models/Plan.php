<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_type',
        'name',
        'amount',
        'currency',
    ];

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'plan_type', 'plan_type');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'plan_type' => 'integer',
            'amount' => 'integer',
        ];
    }
}
