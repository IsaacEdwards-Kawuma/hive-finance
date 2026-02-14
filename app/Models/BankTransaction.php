<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'company_id', 'bank_account_id', 'date', 'type', 'amount',
        'description', 'reference', 'journal_entry_id',
        'transfer_bank_transaction_id', 'reconciled', 'reconciled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:4',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
