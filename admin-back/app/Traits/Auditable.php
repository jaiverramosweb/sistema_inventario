<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Auditable
{
    public function getAuditModule(): string
    {
        return Str::of(class_basename($this))->snake()->plural()->toString();
    }

    public function getAuditExcludedAttributes(): array
    {
        return [
            'created_at',
            'updated_at',
            'deleted_at',
            'password',
            'remember_token',
            'two_factor_secret_encrypted',
            'two_factor_pending_secret_encrypted',
            'two_factor_last_used_step',
        ];
    }

    public function shouldAuditEvent(string $event): bool
    {
        return true;
    }
}
