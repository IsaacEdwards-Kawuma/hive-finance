<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $fillable = [
        'bill_id', 'item_id', 'description', 'quantity', 'price',
        'tax', 'discount', 'total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price' => 'decimal:4',
        'tax' => 'decimal:4',
        'discount' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
