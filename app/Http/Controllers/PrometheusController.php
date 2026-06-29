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
     * Persist Prometheus configuration and invalidate the metrics cache.
     *
     * The old config is read first and its cache key is flushed before the
     * DB write. After the update the new config resolves to a different key
     * and populates fresh on the next scrape, eliminating the stale-cache
     * race window that existed when flush fired after the DB write.
     */
    public function update(UpdatePrometheusRequest $request): RedirectResponse
    {
        $config = Prometheus::config();

        $this->exporter->flush($config);

        $config->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->message('Prometheus settings saved.')
            ->send();

        return back();
    }

    /**
     * Flush the cached metrics output for the current config immediately.
     */
    public function flushCache(): RedirectResponse
    {
        $this->exporter->flush(Prometheus::config());

        InertiaNotification::make()
            ->success()
            ->message('Prometheus metrics cache flushed.')
            ->send();

        return back();
    }

    /**
     * Serve the Prometheus text exposition scrape endpoint.
     *
     * Access is controlled by PrometheusAccessMiddleware — this action
     * only runs when the integration is enabled and the caller's IP passes.
     */
    public function metrics(): HttpResponse
    {
        $config = Prometheus::config();
        $output = $this->exporter->build($config);

        return response($output, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
