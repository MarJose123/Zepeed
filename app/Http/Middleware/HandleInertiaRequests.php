<?php

namespace App\Http\Middleware;

use App\Http\Resources\Account\User\UserResource;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    #[Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $request->user()
                    ? new UserResource($request->user())
                    : null,
            ],
            'speedtest' => fn () => $request->user() ? ProviderResource::collection(
                Provider::query()
                    ->with('latestResult')
                    ->orderBy('id')
                    ->get()
            ) : [],
        ];
    }
}
