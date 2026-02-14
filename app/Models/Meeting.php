<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'location',
        'meeting_at',
        'duration_minutes',
        'status',
        'attendee_user_ids',
    ];

    protected $casts = [
        'meeting_at' => 'datetime',
        'attendee_user_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
