<?php

namespace App\Observers;

use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditableObserver
{
    public function created(Model $model): void
    {
        if (!$this->shouldAudit($model, 'created')) {
            return;
        }

        $excluded = $this->excluded($model);
        $changes = collect($model->getAttributes())
            ->except($excluded)
            ->map(fn ($value, $field) => [
                'field' => $field,
                'before_value' => null,
                'after_value' => $value,
            ])
            ->values()
            ->all();

        app(AuditLogger::class)->log([
            'action' => AuditAction::CREAR,
            'module' => $this->module($model),
            'entity_type' => get_class($model),
            'entity_id' => (string) $model->getKey(),
            'description' => 'Registro creado',
            'status' => true,
            'changes' => $changes,
        ]);
    }

    public function updated(Model $model): void
    {
        if (!$this->shouldAudit($model, 'updated')) {
            return;
        }

        $excluded = $this->excluded($model);
        $dirty = collect($model->getChanges())->except($excluded);

        if ($dirty->isEmpty()) {
            return;
        }

        $changes = $dirty
            ->map(function ($value, $field) use ($model) {
                return [
                    'field' => $field,
                    'before_value' => Arr::get($model->getOriginal(), $field),
                    'after_value' => Arr::get($model->getAttributes(), $field),
                ];
            })
            ->values()
            ->all();

        app(AuditLogger::class)->log([
            'action' => AuditAction::ACTUALIZAR,
            'module' => $this->module($model),
            'entity_type' => get_class($model),
            'entity_id' => (string) $model->getKey(),
            'description' => 'Registro actualizado',
            'status' => true,
            'changes' => $changes,
            'metadata' => [
                'changes_count' => count($changes),
            ],
        ]);
    }

    public function deleted(Model $model): void
    {
        if (!$this->shouldAudit($model, 'deleted')) {
            return;
        }

        $excluded = $this->excluded($model);
        $changes = collect($model->getOriginal())
            ->except($excluded)
            ->map(fn ($value, $field) => [
                'field' => $field,
                'before_value' => $value,
                'after_value' => null,
            ])
            ->values()
            ->all();

        app(AuditLogger::class)->log([
            'action' => AuditAction::ELIMINAR,
            'module' => $this->module($model),
            'entity_type' => get_class($model),
            'entity_id' => (string) $model->getKey(),
            'description' => 'Registro eliminado',
            'status' => true,
            'changes' => $changes,
        ]);
    }

    private function module(Model $model): string
    {
        if (method_exists($model, 'getAuditModule')) {
            return (string) $model->getAuditModule();
        }

        return Str::of(class_basename($model))->snake()->plural()->toString();
    }

    private function excluded(Model $model): array
    {
        if (method_exists($model, 'getAuditExcludedAttributes')) {
            return $model->getAuditExcludedAttributes();
        }

        return ['created_at', 'updated_at', 'deleted_at'];
    }

    private function shouldAudit(Model $model, string $event): bool
    {
        if (method_exists($model, 'shouldAuditEvent')) {
            return (bool) $model->shouldAuditEvent($event);
        }

        return true;
    }
}
