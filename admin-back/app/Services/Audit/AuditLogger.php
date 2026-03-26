<?php

namespace App\Services\Audit;

use App\Jobs\PersistAuditEventJob;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'two_factor_secret_encrypted',
        'two_factor_pending_secret_encrypted',
        'authorization',
        'secret',
        'api_key',
    ];

    public function log(array $payload): void
    {
        PersistAuditEventJob::dispatch($this->enrich($payload))->onQueue('audit');
    }

    public function logSync(array $payload): void
    {
        PersistAuditEventJob::dispatchSync($this->enrich($payload));
    }

    private function enrich(array $payload): array
    {
        $request = request();
        $user = Auth::guard('api')->user() ?? Auth::user();

        $metadata = Arr::get($payload, 'metadata', []);
        $changes = Arr::get($payload, 'changes', []);

        return array_merge([
            'occurred_at' => now()->format('Y-m-d H:i:s.u'),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
            'request_id' => (string) ($request?->headers->get('X-Request-Id') ?: Str::uuid()),
            'correlation_id' => (string) ($request?->headers->get('X-Correlation-Id') ?: Str::uuid()),
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 512, ''),
            'method' => $request?->method(),
            'route_name' => $request?->route()?->getName(),
            'url' => $request?->fullUrl(),
            'status' => true,
            'entity_type' => null,
            'entity_id' => null,
            'metadata' => $this->maskSensitive($metadata),
            'changes' => $this->maskSensitive($changes),
        ], $payload);
    }

    private function maskSensitive(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $masked = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(Str::lower($key), self::SENSITIVE_KEYS, true)) {
                $masked[$key] = '[REDACTED]';
                continue;
            }

            $masked[$key] = is_array($value) ? $this->maskSensitive($value) : $value;
        }

        return $masked;
    }
}
