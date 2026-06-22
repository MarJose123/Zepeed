<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentication Endpoints
 *
 * Manages user authentication, session verification, and token revocation.
 * All endpoints require valid API token authentication via `auth:users-api` guard.
 */
class AuthController extends Controller
{
    /**
     * Get authenticated user profile.
     *
     * Retrieves the current authenticated user's profile information including
     * their ID, name, email, appearance preference (light/dark/system), and
     * account creation timestamp. This endpoint can be used to verify the
     * validity of the current API token and retrieve user metadata.
     *
     * @response 200 {
     *   "data": {
     *     "id": "550e8400-e29b-41d4-a716-446655440000",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "appearance": "dark",
     *     "created_at": "2024-01-01T12:00:00Z"
     *   }
     * }
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'appearance' => $user->appearance,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Revoke API token and logout.
     *
     * Deletes the current API token, effectively logging out the authenticated user.
     * The token becomes invalid immediately and cannot be used for any subsequent API requests.
     * Other tokens for the same user remain active and unaffected.
     * Useful for security-conscious clients that want to invalidate a token on demand.
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        auth()->forgetUser();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}
