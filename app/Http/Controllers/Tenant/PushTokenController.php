<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Push Token Registration — V1 API
 *
 * Allows authenticated users to register and deactivate push notification tokens.
 * Supports polymorphic owners: 'user' (staff/admin) or 'customer'.
 *
 * Routes:
 *   POST   /v1/push-tokens        – register / refresh a device token
 *   DELETE /v1/push-tokens/{id}   – deactivate a token
 *   GET    /v1/push-tokens        – list tokens for the current user
 */
class PushTokenController extends Controller
{
    /**
     * POST /v1/push-tokens
     * Upsert a device token for the authenticated user.
     *
     * Body:
     *   token:       (required) FCM / APNs / Web Push token string
     *   platform:    (required) ios | android | web
     *   device_name: (optional) human-readable label
     *   owner_type:  (optional) user | customer — defaults to 'user'
     *   owner_id:    (optional) override owner ID — defaults to auth()->id()
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => 'required|string|max:512',
            'platform'    => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'owner_type'  => 'nullable|in:user,customer',
            'owner_id'    => 'nullable|integer',
        ]);

        $ownerType = $data['owner_type'] ?? 'user';
        $ownerId   = $data['owner_id']   ?? auth()->id();

        // Upsert: same token string on same platform → update, otherwise create
        $pushToken = PushToken::updateOrCreate(
            [
                'token'      => $data['token'],
                'platform'   => $data['platform'],
                'owner_type' => $ownerType,
                'owner_id'   => $ownerId,
            ],
            [
                'device_name' => $data['device_name'] ?? null,
                'is_active'   => true,
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
        $ownerType = $request->input('owner_type', 'user');
        $ownerId   = auth()->id();

        $tokens = PushToken::forOwner($ownerType, $ownerId)
            ->active()
            ->orderByDesc('last_used_at')
            ->get(['id', 'platform', 'device_name', 'last_used_at', 'created_at']);

        return response()->json(['data' => $tokens]);
    }

    /**
     * DELETE /v1/push-tokens/{id}
     * Deactivate (soft-disable) a push token.
     */
    public function destroy(int $id): JsonResponse
    {
        $token = PushToken::findOrFail($id);

        // Only allow owner to deactivate their own token
        if ($token->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $token->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }
}
