<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'item_category_id', 'name', 'description', 'sku',
        'sale_price', 'purchase_price', 'sale_tax_id', 'purchase_tax_id',
        'track_quantity', 'quantity',
    ];

    protected $casts = [
        'sale_price' => 'decimal:4',
        'purchase_price' => 'decimal:4',
        'quantity' => 'decimal:4',
        'track_quantity' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function saleTax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'sale_tax_id');
    }

    public function purchaseTax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'purchase_tax_id');
    }
}
