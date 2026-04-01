<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderMailProvidersRequest;
use App\Http\Requests\StoreMailProviderRequest;
use App\Http\Requests\UpdateMailProviderRequest;
use App\Http\Resources\MailProviderResource;
use App\Models\MailProvider;
use App\Services\InertiaNotification;
use App\Services\MailProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MailProviderController extends Controller
{
    public function __construct(
        private readonly MailProviderService $service,
    ) {}

    public function index(): Response
    {
        $providers = MailProvider::query()
            ->ordered()
            ->get();

        return Inertia::render('integration/MailProviders', [
            'providers' => MailProviderResource::collection($providers)->resolve(),
        ]);
    }

    public function store(StoreMailProviderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $priority = MailProvider::query()->max('priority') + 1;

        $provider = MailProvider::query()->create([
            ...$validated,
            'priority' => $priority,
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Provider added')
            ->message("Mail provider \"{$validated['label']}\" has been configured.")
            ->send();

        // Flash the new ID so the wizard can use it for the test step
        Inertia::flash('mail_provider_id', $provider->id);

        return back();
    }

    public function update(
        UpdateMailProviderRequest $request,
        MailProvider $mailProvider,
    ): RedirectResponse {
        $mailProvider->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Provider updated')
            ->message("\"{$mailProvider->label}\" has been updated.")
            ->send();

        return back();
    }

    public function destroy(MailProvider $mailProvider): RedirectResponse
    {
        $label = $mailProvider->label;
        $mailProvider->delete();

        // Reorder remaining providers to close the gap
        MailProvider::query()
            ->ordered()
            ->get()
            ->each(function (MailProvider $p, int $index) {
                $p->update(['priority' => $index + 1]);
            });

        InertiaNotification::make()
            ->success()
            ->title('Provider removed')
            ->message("\"{$label}\" has been removed.")
            ->send();

        return back();
    }

    public function reorder(ReorderMailProvidersRequest $request): RedirectResponse
    {
        $this->service->reorder($request->validated('ordered_ids'));

        InertiaNotification::make()
            ->success()
            ->title('Order saved')
            ->message('Fallback chain has been updated.')
            ->send();

        return back();
    }

    public function test(Request $request, MailProvider $mailProvider): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->service->sendTestEmail($mailProvider, $request->input('email'));
            $mailProvider->recordSuccess();

            InertiaNotification::make()
                ->success()
                ->title('Test email sent')
                ->message("Test email delivered via {$mailProvider->driver->label()}.")
                ->send();

            return back();

        } catch (Throwable $e) {
            $mailProvider->recordFailure();

            // Return as validation error so Inertia onError fires in the wizard
            return back()->withErrors([
                'test' => $e->getMessage(),
            ]);
        }
    }
}
