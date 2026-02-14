<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'type', 'symbol', 'cost_basis', 'current_value',
        'currency', 'date_acquired', 'account_id', 'notes',
    ];

    protected $casts = [
        'cost_basis' => 'decimal:4',
        'current_value' => 'decimal:4',
        'date_acquired' => 'date',
    ];

    protected $appends = ['gain_loss', 'gain_loss_percent'];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class)->orderByDesc('trans_date');
    }

    public function getGainLossAttribute(): float
    {
        return (float) $this->current_value - (float) $this->cost_basis;
    }

    public function getGainLossPercentAttribute(): ?float
    {
        $cost = (float) $this->cost_basis;
        if ($cost <= 0) {
            return null;
        }
        return round((((float) $this->current_value - $cost) / $cost) * 100, 2);
    }
}
