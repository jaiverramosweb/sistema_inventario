<?php

namespace App\Listeners\Audit;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Login;

class LoginListener
{
    public function handle(Login $event): void
    {
        app(AuditLogger::class)->log([
            'action' => AuditAction::INICIO_SESION,
            'module' => 'auth',
            'entity_type' => get_class($event->user),
            'entity_id' => (string) $event->user->getAuthIdentifier(),
            'description' => 'Inicio de sesion exitoso',
            'status' => true,
        ]);
    }
}
