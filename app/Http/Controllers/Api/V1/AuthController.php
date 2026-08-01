<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'Auth',
    description: 'Manages session verification and token revocation. All endpoints require valid API token authentication via the auth:users-api guard.',
    weight: 2,
)]
/**
 * Authentication Endpoints
 *
 * Manages session verification and token revocation.
 * All endpoints require valid API token authentication via the auth:users-api guard.
 */
class AuthController extends Controller
{
    /**
     * Get authenticated user profile.
     *
     * Retrieves the current authenticated user's profile information including
     * their ID, name, email, appearance preference, and account creation timestamp.
     * Can be used to verify token validity and retrieve user metadata.
     *
     * @param Request $request
     */
    #[Endpoint(title: 'Get authenticated user', description: "Retrieve the current authenticated user's profile information including their ID, name, email, appearance preference, and account creation timestamp.")]
    public function user(Request $request): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Revoke API token and logout.
     *
     * Deletes the current API token, effectively logging out the authenticated user.
     * The token becomes invalid immediately and cannot be used for subsequent requests.
     * Other tokens for the same user remain active and unaffected.
     *
     * @param Request $request
     */
    #[Endpoint(title: 'Revoke token and logout', description: 'Deletes the current API token, effectively logging out the authenticated user. Other tokens for the same user remain active and unaffected.')]
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        auth()->forgetUser();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Logged out successfully',
        ], 200);
    }
}
