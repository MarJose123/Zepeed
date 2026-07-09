<?php

namespace App\Jobs;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Events\Export\ExportCompletedEvent;
use App\Events\Export\ExportFailedEvent;
use App\Models\ExportRequest;
use App\Models\SpeedResult;
use App\Services\Export\CsvWriterService;
use App\Services\Export\JsonWriterService;
use App\Services\Export\XlsxWriterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSpeedResultExportJob implements ShouldQueue
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
            $module = $this->exportRequest->module;
            $metric = $this->resolveMetric($module);
            $filename = $this->buildFilename($module);

            $query = SpeedResult::query()
                ->select('speed_results.*', 'providers.name as provider_name')
                ->leftJoin('providers', 'providers.slug', '=', 'speed_results.provider_slug')
                ->where('speed_results.status', 'success')
                ->when($filters['provider'] ?? null, static fn ($q, $v) => $q->where('speed_results.provider_slug', $v))
                ->whereBetween('speed_results.measured_at', [
                    "{$filters['date_from']} 00:00:00",
                    "{$filters['date_to']} 23:59:59",
                ])
                ->latest('speed_results.measured_at');

            $rows = $this->buildRows($query->cursor(), $metric);
            $headers = $this->buildHeaders($metric);
            $format = $this->exportRequest->format;

            $path = match ($format) {
                ExportFormat::Csv  => $csv->write($rows, $headers, $filename),
                ExportFormat::Xlsx => $xlsx->write($rows, $headers, $filename),
                ExportFormat::Json => $json->write($rows, $filename),
            };

            $count = SpeedResult::query()
                ->where('speed_results.status', 'success')
                ->when($filters['provider'] ?? null, static fn ($q, $v) => $q->where('provider_slug', $v))
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

            event(new ExportCompletedEvent($this->exportRequest->refresh()));

        } catch (Throwable $e) {
            $this->exportRequest->update([
                'status'          => ExportStatus::Failed,
                'failure_message' => $e->getMessage(),
            ]);

            event(new ExportFailedEvent($this->exportRequest->refresh()));

            Log::error('GenerateSpeedResultExportJob failed.', [
                'export_id' => $this->exportRequest->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param iterable<SpeedResult> $cursor
     *
     * @return iterable<array<string, mixed>>
     */
    private function buildRows(iterable $cursor, string $metric): iterable
    {
        foreach ($cursor as $row) {
            $base = [
                'id'              => $row->id,
                'provider'        => $row->provider_name ?? $row->provider_slug,
                'measured_at'     => $row->measured_at->toIso8601String(),
                'download_mbps'   => $row->download_mbps,
                'upload_mbps'     => $row->upload_mbps,
                'ping_ms'         => $row->ping_ms,
                'jitter_ms'       => $row->jitter_ms,
                'server_name'     => $row->server_name,
                'server_location' => $row->server_location,
                'isp'             => $row->isp,
            ];

            yield $base;
        }
    }

    /** @return list<string> */
    private function buildHeaders(string $metric): array
    {
        return ['ID', 'Provider', 'Measured At', 'Download (Mbps)', 'Upload (Mbps)', 'Ping (ms)', 'Jitter (ms)', 'Server', 'Location', 'ISP'];
    }

    private function resolveMetric(ExportModule $module): string
    {
        return match ($module) {
            ExportModule::SpeedDownload => 'download',
            ExportModule::SpeedUpload   => 'upload',
            ExportModule::SpeedLatency  => 'ping',
            default                     => 'download',
        };
    }

    private function buildFilename(ExportModule $module): string
    {
        return "{$module->value}_{$this->exportRequest->id}";
    }
}
