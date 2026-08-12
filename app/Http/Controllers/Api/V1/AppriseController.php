<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AppriseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppriseRequest;
use App\Http\Requests\UpdateAppriseRequest;
use App\Http\Resources\Api\AppriseResource;
use App\Models\Apprise;
use App\Services\AppriseService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

#[Group(
    name: 'Apprise',
    description: 'Manage Apprise notification gateway configurations. Each instance points at an Apprise API server and carries its own tags and optional Basic Auth credentials. Apprise instances are triggered by workflow rules (speedtest) or ping alert rules when their conditions match.',
    weight: 12,
)]
/**
 * Apprise Endpoints
 *
 * Manage Apprise notification gateway configurations. Each instance points at
 * an Apprise API server and carries its own tags and optional Basic Auth
 * credentials. Apprise instances are triggered by workflow rules (speedtest)
 * or ping alert rules when their conditions match.
 */
class AppriseController extends Controller
{
    public function __construct(
        private readonly AppriseService $service,
    ) {}

    /**
     * List Apprise instances with pagination.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     */
    #[Endpoint(title: 'List Apprise instances', description: 'List Apprise notification gateway configurations with pagination.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $apprises = Apprise::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return AppriseResource::collection($apprises)->additional([
            'success' => filled($apprises),
            'code'    => 200,
        ]);
    }

    /**
     * Show a single Apprise instance.
     *
     * @param Apprise $apprise
     */
    #[Endpoint(title: 'Show Apprise instance', description: 'Show a single Apprise notification gateway configuration.')]
    public function show(Apprise $apprise): JsonResource
    {
        return AppriseResource::make($apprise)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create a new Apprise instance.
     *
     * The `password` is stored encrypted and is never returned by the API —
     * only its presence (together with a username) is exposed via
     * `has_credentials`.
     *
     * @param StoreAppriseRequest $request
     */
    #[Endpoint(title: 'Create Apprise instance', description: 'Create a new Apprise instance. The password is stored encrypted and is never returned by the API - only its presence is exposed via has_credentials.')]
    public function store(StoreAppriseRequest $request): JsonResponse
    {
        /** @var Apprise $apprise */
        $apprise = Apprise::query()->create($request->validated());

        return AppriseResource::make($apprise->refresh())
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Apprise instance created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing Apprise instance.
     *
     * An absent or blank `password` keeps the existing credential; a non-empty
     * `password` replaces it.
     *
     * @param UpdateAppriseRequest $request
     * @param Apprise              $apprise
     */
    #[Endpoint(title: 'Update Apprise instance', description: 'Update an existing Apprise instance. An absent or blank password keeps the existing credential.')]
    public function update(UpdateAppriseRequest $request, Apprise $apprise): JsonResource
    {
        $apprise->update($request->passwordAwareValidated());

        return AppriseResource::make($apprise->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Apprise instance updated successfully.',
        ]);
    }

    /**
     * Delete an Apprise instance.
     *
     * @param Apprise $apprise
     */
    #[Endpoint(title: 'Delete Apprise instance', description: 'Delete an Apprise notification gateway configuration.')]
    public function destroy(Apprise $apprise): JsonResponse
    {
        $apprise->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Apprise instance deleted successfully.',
        ]);
    }

    /**
     * Send a test notification to the Apprise instance.
     *
     * Performs a synchronous HTTP request. Returns 200 on success and 422
     * when the notification could not be delivered, including connection
     * failures. Error messages never expose credentials.
     *
     * @param Apprise $apprise
     */
    #[Endpoint(title: 'Send test notification', description: 'Send a synchronous test notification to the Apprise instance. Returns 200 on success and 422 when the notification fails, including connection failures.')]
    public function test(Apprise $apprise): JsonResponse
    {
        try {
            $this->service->sendTest($apprise);

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Test notification sent successfully.',
            ]);
        } catch (AppriseException $e) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Test notification failed: ' . $e->getMessage(),
            ], 422);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'code'    => 422,
                'message' => 'Test notification failed.',
            ], 422);
        }
    }
}
