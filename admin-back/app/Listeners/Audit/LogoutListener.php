<?php

namespace App\Listeners\Audit;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogoutListener
{
    public function handle(Logout $event): void
    {
        app(AuditLogger::class)->log([
            'action' => AuditAction::CIERRE_SESION,
            'module' => 'auth',
            'entity_type' => $event->user ? get_class($event->user) : null,
            'entity_id' => $event->user ? (string) $event->user->getAuthIdentifier() : null,
            'description' => 'Cierre de sesion',
            'status' => true,
        ]);
    }
}
