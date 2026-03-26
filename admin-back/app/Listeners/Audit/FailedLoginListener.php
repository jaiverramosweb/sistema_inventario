<?php

namespace App\Listeners\Audit;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Failed;

class FailedLoginListener
{
    public function handle(Failed $event): void
    {
        app(AuditLogger::class)->log([
            'action' => AuditAction::INICIO_SESION_FALLIDO,
            'module' => 'auth',
            'description' => 'Intento fallido de inicio de sesion',
            'status' => false,
            'metadata' => [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ],
        ]);
    }
}
