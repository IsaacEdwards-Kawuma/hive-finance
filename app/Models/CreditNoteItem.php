<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id', 'description', 'quantity', 'price', 'tax', 'discount', 'total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price' => 'decimal:4',
        'tax' => 'decimal:4',
        'discount' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }
}
