<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEventChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_event_id',
        'field',
        'before_value',
        'after_value',
    ];

    protected $casts = [
        'before_value' => 'array',
        'after_value' => 'array',
    ];

    public function auditEvent(): BelongsTo
    {
        return $this->belongsTo(AuditEvent::class);
    }
}
