<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuditRouteAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->user('api')) {
            return $response;
        }

        if (!$request->isMethod('GET')) {
            return $response;
        }

        if (!$response->isSuccessful()) {
            return $response;
        }

        $path = trim((string) $request->path(), '/');
        if ($path === '' || str_starts_with($path, 'api/audit')) {
            return $response;
        }

        $module = $this->resolveModule($request);
        if ($module === 'auth') {
            return $response;
        }

        $cacheKey = sprintf(
            'audit:navigation:%s:%s:%s',
            (string) $request->user('api')?->id,
            $module,
            (string) $request->route()?->getName()
        );

        if (Cache::has($cacheKey)) {
            return $response;
        }

        Cache::put($cacheKey, true, now()->addMinutes(3));

        app(AuditLogger::class)->log([
            'action' => AuditAction::NAVEGACION,
            'module' => $module,
            'description' => 'Acceso al modulo desde API',
            'status' => true,
            'metadata' => [
                'path' => $request->path(),
                'query' => $request->query(),
            ],
        ]);

        return $response;
    }

    private function resolveModule(Request $request): string
    {
        $parts = explode('/', trim((string) $request->path(), '/'));
        if (($parts[0] ?? null) === 'api') {
            return $parts[1] ?? 'system';
        }

        return $parts[0] ?? 'system';
    }
}
