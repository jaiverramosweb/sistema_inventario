<?php

namespace App\Http\Controllers\Audit;

use App\Exports\AuditEventsExport;
use App\Http\Controllers\Controller;
use App\Models\AuditExport;
use App\Services\Audit\AuditQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditExportController extends Controller
{
    public function export(Request $request, AuditQueryBuilder $queryBuilder): BinaryFileResponse
    {
        abort_unless($request->user('api')?->can('export_audit_logs'), 403);

        $validated = $request->validate([
            'format' => 'nullable|in:csv,xlsx',
        ]);

        $format = $validated['format'] ?? 'csv';
        $query = $queryBuilder->fromRequest($request)->orderByDesc('occurred_at');

        $filename = 'audit_logs_' . now()->format('Ymd_His') . '.' . $format;

        AuditExport::create([
            'requested_by' => $request->user('api')?->id,
            'filters' => $request->except(['format']),
            'format' => $format,
            'status' => 'completed',
            'file_path' => $filename,
        ]);

        $writerType = $format === 'xlsx'
            ? \Maatwebsite\Excel\Excel::XLSX
            : \Maatwebsite\Excel\Excel::CSV;

        return Excel::download(new AuditEventsExport($query), $filename, $writerType, [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
            'X-Request-Id' => (string) Str::uuid(),
        ]);
    }
}
