<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'occurred_at',
        'user_id',
        'user_name',
        'session_id',
        'request_id',
        'correlation_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'status',
        'ip',
        'user_agent',
        'method',
        'route_name',
        'url',
        'metadata',
        'event_hash',
        'prev_hash',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(AuditEventChange::class);
    }
}
