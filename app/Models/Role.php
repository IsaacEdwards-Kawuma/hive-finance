<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['company_id', 'name', 'display_name', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class, 'role_id');
    }
}
