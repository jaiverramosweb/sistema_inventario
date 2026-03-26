<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\AuditQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditQueryBuilder $queryBuilder): JsonResponse
    {
        abort_unless($request->user('api')?->can('view_audit_logs'), 403);

        $perPage = min(max($request->integer('per_page', 25), 1), 200);
        $sort = in_array($request->string('sort')->toString(), ['occurred_at', 'id'], true)
            ? $request->string('sort')->toString()
            : 'occurred_at';
        $dir = $request->string('dir')->lower()->toString() === 'asc' ? 'asc' : 'desc';

        $query = $queryBuilder->fromRequest($request);
        $page = $query->orderBy($sort, $dir)->paginate($perPage);

        return response()->json($page);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user('api')?->can('view_audit_logs'), 403);

        $event = AuditEvent::query()
            ->with(['changes', 'user:id,name,email'])
            ->findOrFail($id);

        return response()->json($event);
    }

    public function navigation(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        abort_unless($request->user('api'), 401);

        $data = $request->validate([
            'route_name' => 'nullable|string|max:150',
            'module' => 'required|string|max:80',
            'path' => 'required|string|max:1000',
            'title' => 'nullable|string|max:120',
        ]);

        $auditLogger->log([
            'action' => AuditAction::NAVEGACION,
            'module' => $data['module'],
            'description' => 'Navegacion de interfaz',
            'status' => true,
            'metadata' => [
                'path' => $data['path'],
                'title' => $data['title'] ?? null,
                'route_name' => $data['route_name'] ?? null,
            ],
        ]);

        return response()->json(['message' => 'ok']);
    }

    public function filters(Request $request): JsonResponse
    {
        abort_unless($request->user('api')?->can('view_audit_logs'), 403);

        $actions = AuditEvent::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $modules = AuditEvent::query()->select('module')->distinct()->orderBy('module')->pluck('module');
        $users = AuditEvent::query()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(user_name) as user_name')
            ->groupBy('user_id')
            ->orderBy('user_name')
            ->limit(500)
            ->get();

        return response()->json([
            'actions' => $actions,
            'modules' => $modules,
            'users' => $users,
        ]);
    }
}
