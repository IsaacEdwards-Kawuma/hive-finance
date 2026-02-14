<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name', 'email', 'address', 'logo', 'fiscal_year_start',
        'default_currency', 'tax_id', 'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'company_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'company_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'company_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'company_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'company_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'company_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'company_id');
    }
}
