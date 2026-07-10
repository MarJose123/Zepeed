<?php

namespace App\Http\Controllers;

use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Http\Requests\ExportPingResultRequest;
use App\Http\Requests\ExportSpeedResultRequest;
use App\Jobs\GeneratePingResultExportJob;
use App\Jobs\GenerateSpeedResultExportJob;
use App\Models\ExportRequest;
use App\Models\User;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    public function storeSpeedDownload(ExportSpeedResultRequest $request): RedirectResponse
    {
        return $this->dispatchSpeedExport($request, ExportModule::SpeedDownload, 'speedtest.results.download');
    }

    public function storeSpeedUpload(ExportSpeedResultRequest $request): RedirectResponse
    {
        return $this->dispatchSpeedExport($request, ExportModule::SpeedUpload, 'speedtest.results.upload');
    }

    public function storeSpeedLatency(ExportSpeedResultRequest $request): RedirectResponse
    {
        return $this->dispatchSpeedExport($request, ExportModule::SpeedLatency, 'speedtest.results.latency');
    }

    public function storePingResult(ExportPingResultRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $filters = [
            'target'    => $validated['target'] === '__all__' ? null : ($validated['target'] ?? null),
            'date_from' => $validated['date_from'],
            'date_to'   => $validated['date_to'],
        ];

        $export = ExportRequest::query()->create([
            'user_id' => $user->id,
            'module'  => ExportModule::PingResult,
            'format'  => $validated['format'],
            'status'  => ExportStatus::Pending,
            'filters' => $filters,
        ]);

        dispatch(new GeneratePingResultExportJob($export));

        InertiaNotification::make()
            ->info()
            ->title('Export queued')
            ->message("Your ping results export is being generated. We'll notify you here when it's ready.")
            ->send();

        return to_route('speedtest.network.ping-results.index');
    }

    public function download(Request $request, ExportRequest $exportRequest): StreamedResponse
    {
        Gate::allowIf((int) $request->user()?->id === (int) $exportRequest->user_id);

        abort_if($exportRequest->status !== ExportStatus::Completed, 404, 'Export not ready.');
        abort_if($exportRequest->file_path === null, 404, 'Export file missing.');
        abort_if($exportRequest->expires_at?->isPast(), 410, 'Export has expired.');

        $fullPath = storage_path("app/private/{$exportRequest->file_path}");

        abort_unless(file_exists($fullPath), 404, 'Export file not found.');

        $filename = basename($fullPath);

        return response()->streamDownload(
            static function () use ($fullPath): void {
                readfile($fullPath);
            },
            $filename,
            ['Content-Type' => $exportRequest->format->mimeType()],
        );
    }

    private function dispatchSpeedExport(
        ExportSpeedResultRequest $request,
        ExportModule $module,
        string $redirectRoute,
    ): RedirectResponse {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $filters = [
            'provider'  => $validated['provider'] === '__all__' ? null : ($validated['provider'] ?? null),
            'date_from' => $validated['date_from'],
            'date_to'   => $validated['date_to'],
        ];

        $export = ExportRequest::query()->create([
            'user_id' => $user->id,
            'module'  => $module,
            'format'  => $validated['format'],
            'status'  => ExportStatus::Pending,
            'filters' => $filters,
        ]);

        dispatch(new GenerateSpeedResultExportJob($export));

        InertiaNotification::make()
            ->info()
            ->title('Export queued')
            ->message("Your export is being generated. We'll notify you here when it's ready to download.")
            ->send();

        return to_route($redirectRoute);
    }
}
