<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePrometheusRequest;
use App\Models\Prometheus;
use App\Services\InertiaNotification;
use App\Services\PrometheusExporterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Prometheus\RenderTextFormat;

class PrometheusController extends Controller
{
    public function __construct(
        private readonly PrometheusExporterService $exporter,
    ) {}

    /**
     * Render the Prometheus integration settings page.
     */
    public function index(): Response
    {
        return Inertia::render('integration/Prometheus', [
            'config' => Prometheus::config(),
        ]);
    }

    /**
     * Persist Prometheus configuration and flush the metrics cache.
     */
    public function update(UpdatePrometheusRequest $request): RedirectResponse
    {
        Prometheus::config()->update($request->validated());

        $this->exporter->flush();

        InertiaNotification::make()
            ->success()
            ->message('Prometheus settings saved.')
            ->send();

        return back();
    }

    /**
     * Flush the cached metrics output immediately.
     */
    public function flushCache(): RedirectResponse
    {
        $this->exporter->flush();

        InertiaNotification::make()
            ->success()
            ->message('Prometheus metrics cache flushed.')
            ->send();

        return back();
    }

    /**
     * Serve the Prometheus text exposition scrape endpoint.
     *
     * Access controlled by PrometheusAccessMiddleware — this action
     * only runs when the integration is enabled and the caller's IP is allowed.
     */
    public function metrics(): HttpResponse
    {
        $output = $this->exporter->build(Prometheus::config());

        return response($output, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
