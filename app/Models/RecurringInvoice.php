<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoice extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'frequency', 'start_date', 'next_run_date', 'end_date', 'template', 'enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_run_date' => 'date',
        'end_date' => 'date',
        'template' => 'array',
        'enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
