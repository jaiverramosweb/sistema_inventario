<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditEventsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return (clone $this->query)->select([
            'id',
            'occurred_at',
            'user_id',
            'user_name',
            'action',
            'module',
            'entity_type',
            'entity_id',
            'description',
            'status',
            'ip',
            'method',
            'route_name',
            'url',
            'metadata',
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Usuario ID',
            'Usuario',
            'Accion',
            'Modulo',
            'Entidad',
            'Entidad ID',
            'Descripcion',
            'Estado',
            'IP',
            'Metodo',
            'Ruta',
            'URL',
            'Metadata',
        ];
    }

    public function map($event): array
    {
        return [
            $event->id,
            optional($event->occurred_at)->format('Y-m-d H:i:s'),
            $event->user_id,
            $event->user_name,
            $event->action,
            $event->module,
            $event->entity_type,
            $event->entity_id,
            $event->description,
            $event->status ? 'success' : 'error',
            $event->ip,
            $event->method,
            $event->route_name,
            $event->url,
            json_encode($event->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
}
