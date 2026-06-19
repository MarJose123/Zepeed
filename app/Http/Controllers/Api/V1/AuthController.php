<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Get the authenticated user.
     *
     * Retrieves the current authenticated user's profile information
     * including id, name, email, appearance preference, and creation timestamp.
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
     * Revoke the current API token and logout.
     *
     * Deletes the current API token, effectively logging out the authenticated user.
     * The token becomes invalid immediately and cannot be used for subsequent requests.
     * Other tokens for the same user remain active.
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
