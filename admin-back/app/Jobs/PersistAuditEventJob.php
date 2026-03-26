<?php

namespace App\Jobs;

use App\Models\AuditEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PersistAuditEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private readonly array $payload)
    {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $previous = AuditEvent::query()
                ->select(['id', 'event_hash'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $changes = Arr::get($this->payload, 'changes', []);
            $metadata = Arr::get($this->payload, 'metadata', []);

            $record = Arr::except($this->payload, ['changes']);
            $record['metadata'] = $metadata;
            $record['prev_hash'] = $previous?->event_hash;
            $record['event_hash'] = $this->computeHash($record, $previous?->event_hash);

            $event = AuditEvent::create($record);

            if (is_array($changes) && !empty($changes)) {
                $rows = collect($changes)
                    ->filter(fn ($change) => is_array($change) && isset($change['field']))
                    ->map(function (array $change) {
                        return [
                            'field' => (string) $change['field'],
                            'before_value' => Arr::has($change, 'before_value') ? $change['before_value'] : null,
                            'after_value' => Arr::has($change, 'after_value') ? $change['after_value'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })
                    ->values()
                    ->all();

                if (!empty($rows)) {
                    $event->changes()->createMany($rows);
                }
            }
        });
    }

    private function computeHash(array $record, ?string $prevHash): string
    {
        $hashable = [
            'occurred_at' => Arr::get($record, 'occurred_at'),
            'user_id' => Arr::get($record, 'user_id'),
            'action' => Arr::get($record, 'action'),
            'module' => Arr::get($record, 'module'),
            'entity_type' => Arr::get($record, 'entity_type'),
            'entity_id' => Arr::get($record, 'entity_id'),
            'description' => Arr::get($record, 'description'),
            'status' => Arr::get($record, 'status'),
            'metadata' => Arr::get($record, 'metadata'),
        ];

        return hash('sha256', ($prevHash ?? '') . json_encode($hashable, JSON_UNESCAPED_SLASHES));
    }
}
