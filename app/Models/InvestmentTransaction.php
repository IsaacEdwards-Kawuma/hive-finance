<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentTransaction extends Model
{
    protected $fillable = [
        'investment_id', 'trans_date', 'type', 'quantity', 'price_per_unit', 'amount', 'note',
    ];

    protected $casts = [
        'trans_date' => 'date',
        'quantity' => 'decimal:6',
        'price_per_unit' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
