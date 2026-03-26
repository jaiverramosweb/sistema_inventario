<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditQueryBuilder
{
    public function fromRequest(Request $request): Builder
    {
        $query = AuditEvent::query()->with('changes')->with('user:id,name');

        $query->when($request->filled('user_id'), fn (Builder $builder) => $builder->where('user_id', $request->integer('user_id')))
            ->when($request->filled('module'), fn (Builder $builder) => $builder->where('module', $request->string('module')))
            ->when($request->filled('action'), fn (Builder $builder) => $builder->where('action', $request->string('action')))
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', (bool) $request->integer('status')))
            ->when($request->filled('from'), fn (Builder $builder) => $builder->where('occurred_at', '>=', $request->date('from')?->startOfDay()))
            ->when($request->filled('to'), fn (Builder $builder) => $builder->where('occurred_at', '<=', $request->date('to')?->endOfDay()))
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = '%' . $request->string('search') . '%';
                $builder->where(function (Builder $nested) use ($search) {
                    $nested->where('description', 'ilike', $search)
                        ->orWhere('entity_id', 'ilike', $search)
                        ->orWhere('user_name', 'ilike', $search)
                        ->orWhere('module', 'ilike', $search)
                        ->orWhere('action', 'ilike', $search);
                });
            });

        return $query;
    }
}
