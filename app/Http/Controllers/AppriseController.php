<?php

namespace App\Http\Controllers;

use App\Exceptions\AppriseException;
use App\Http\Requests\StoreAppriseRequest;
use App\Http\Requests\UpdateAppriseRequest;
use App\Http\Resources\AppriseResource;
use App\Models\Apprise;
use App\Services\AppriseService;
use App\Services\InertiaNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AppriseController extends Controller
{
    public function __construct(
        private readonly AppriseService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('integration/Apprises', [
            'apprises' => AppriseResource::collection(
                Apprise::query()->latest()->get()
            )->resolve(),
        ]);
    }

    public function store(StoreAppriseRequest $request): RedirectResponse
    {
        Apprise::query()->create($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Apprise instance created')
            ->message("Apprise \"{$request->validated('name')}\" has been added.")
            ->send();

        return back();
    }

    public function update(UpdateAppriseRequest $request, Apprise $apprise): RedirectResponse
    {
        $apprise->update($request->passwordAwareValidated());

        InertiaNotification::make()
            ->success()
            ->title('Apprise instance updated')
            ->message("\"{$apprise->name}\" has been updated.")
            ->send();

        return back();
    }

    public function destroy(Apprise $apprise): RedirectResponse
    {
        $name = $apprise->name;
        $apprise->delete();

        InertiaNotification::make()
            ->success()
            ->title('Apprise instance removed')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return back();
    }

    public function test(Request $request, Apprise $apprise): RedirectResponse
    {
        try {
            $this->service->sendTest($apprise);

            InertiaNotification::make()
                ->success()
                ->title('Test notification sent')
                ->message("\"{$apprise->name}\" accepted the test notification.")
                ->send();
        } catch (Throwable $e) {
            InertiaNotification::make()
                ->error()
                ->title('Test notification failed')
                ->message($e->getMessage())
                ->send();
        }

        return back();
    }

    /**
     * Validate a configuration payload by sending a test notification without
     * persisting it — used by the creation form's "Test connection" button.
     * Credentials are never included in error messages.
     *
     * @param StoreAppriseRequest $request
     */
    public function testConfig(StoreAppriseRequest $request): JsonResponse
    {
        try {
            $this->service->testConfiguration($request->validated());

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Connection successful — the Apprise server accepted the test notification.',
            ]);
        } catch (AppriseException $e) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Connection failed.',
            ], 422);
        }
    }
}
