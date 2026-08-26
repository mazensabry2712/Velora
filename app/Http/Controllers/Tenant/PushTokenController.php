<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Push Token Registration — V1 API
 *
 * Allows authenticated users to register and deactivate their own push tokens.
 */
class PushTokenController extends Controller
{
    /**
     * POST /v1/push-tokens
     * Upsert a device token for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => 'required|string|max:512',
            'platform'    => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        // Ownership always comes from the authenticated user. Never trust
        // owner_type/owner_id supplied by the client, otherwise a user could
        // register or overwrite another user's device token.
        $ownerType = 'user';
        $ownerId   = (int) auth()->id();

        $pushToken = PushToken::updateOrCreate(
            [
                'token'      => $data['token'],
                'platform'   => $data['platform'],
                'owner_type' => $ownerType,
                'owner_id'   => $ownerId,
            ],
            [
                'device_name'  => $data['device_name'] ?? null,
                'is_active'    => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'data' => $pushToken], 201);
    }

    /**
     * GET /v1/push-tokens
     * List active tokens for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = PushToken::forOwner('user', (int) auth()->id())
            ->active()
            ->orderByDesc('last_used_at')
            ->get(['id', 'platform', 'device_name', 'last_used_at', 'created_at']);

        return response()->json(['success' => true, 'data' => $tokens]);
    }

    /**
     * DELETE /v1/push-tokens/{id}
     * Deactivate (soft-disable) a push token owned by the authenticated user.
     */
    public function destroy(int $id): JsonResponse
    {
        $token = PushToken::forOwner('user', (int) auth()->id())->findOrFail($id);
        $token->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }
}
