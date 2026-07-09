<?php

// app/Jobs/GeneratePingResultExportJob.php

namespace App\Jobs;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Events\Export\ExportCompletedEvent;
use App\Events\Export\ExportFailedEvent;
use App\Models\ExportRequest;
use App\Models\PingResult;
use App\Models\User;
use App\Notifications\Export\ExportCompletedNotification;
use App\Notifications\Export\ExportFailedNotification;
use App\Services\Export\CsvWriterService;
use App\Services\Export\JsonWriterService;
use App\Services\Export\XlsxWriterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePingResultExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public readonly ExportRequest $exportRequest,
    ) {}

    public function handle(CsvWriterService $csv, XlsxWriterService $xlsx, JsonWriterService $json): void
    {
        $this->exportRequest->update(['status' => ExportStatus::Processing]);

        try {
            $filters = $this->exportRequest->filters;
            $filename = "ping_result_{$this->exportRequest->id}";

            $query = PingResult::query()
                ->with('target')
                ->when($filters['target'] ?? null, static fn ($q, $v) => $q->where('ping_target_id', $v))
                ->whereBetween('measured_at', [
                    "{$filters['date_from']} 00:00:00",
                    "{$filters['date_to']} 23:59:59",
                ])
                ->latest('measured_at');

            $rows = $this->buildRows($query->cursor());
            $headers = ['ID', 'Target', 'Host', 'Status', 'Packets Sent', 'Packets Received', 'Loss (%)', 'Min (ms)', 'Avg (ms)', 'Max (ms)', 'Std Dev (ms)', 'Measured At'];
            $format = $this->exportRequest->format;

            $path = match ($format) {
                ExportFormat::Csv  => $csv->write($rows, $headers, $filename),
                ExportFormat::Xlsx => $xlsx->write($rows, $headers, $filename),
                ExportFormat::Json => $json->write($rows, $filename),
            };

            $count = PingResult::query()
                ->when($filters['target'] ?? null, static fn ($q, $v) => $q->where('ping_target_id', $v))
                ->whereBetween('measured_at', [
                    "{$filters['date_from']} 00:00:00",
                    "{$filters['date_to']} 23:59:59",
                ])
                ->count();

            $this->exportRequest->update([
                'status'     => ExportStatus::Completed,
                'file_path'  => $path,
                'row_count'  => $count,
                'expires_at' => now()->addDays(7),
            ]);

            $refreshed = $this->exportRequest->refresh();

            User::query()->find($refreshed->user_id)
                ?->notify(new ExportCompletedNotification($refreshed));

            event(new ExportCompletedEvent($refreshed));

        } catch (Throwable $e) {
            $this->exportRequest->update([
                'status'          => ExportStatus::Failed,
                'failure_message' => $e->getMessage(),
            ]);

            $refreshed = $this->exportRequest->refresh();

            User::query()->find($refreshed->user_id)
                ?->notify(new ExportFailedNotification($refreshed));

            event(new ExportFailedEvent($refreshed));

            Log::error('GeneratePingResultExportJob failed.', [
                'export_id' => $this->exportRequest->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param iterable<PingResult> $cursor
     *
     * @return iterable<array<string, mixed>>
     */
    private function buildRows(iterable $cursor): iterable
    {
        foreach ($cursor as $row) {
            yield [
                'id'                  => $row->id,
                'target'              => $row->relationLoaded('target') ? $row->target->label : null,
                'host'                => $row->relationLoaded('target') ? $row->target->host : null,
                'status'              => $row->status->value,
                'packets_sent'        => $row->packets_sent,
                'packets_received'    => $row->packets_received,
                'packet_loss_percent' => $row->packet_loss_percent,
                'min_ms'              => $row->min_ms,
                'avg_ms'              => $row->avg_ms,
                'max_ms'              => $row->max_ms,
                'stddev_ms'           => $row->stddev_ms,
                'measured_at'         => $row->measured_at->toIso8601String(),
            ];
        }
    }
}
