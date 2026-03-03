<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * GET /v1/settings — return the current tenant settings as JSON.
     */
    public function show(string $id = '1'): JsonResponse
    {
        $settings = Setting::first();

        if (! $settings) {
            return response()->json([
                'success'  => true,
                'data'     => null,
                'message'  => 'No settings configured yet.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    /**
     * PUT /v1/settings — update (or create) the tenant settings.
     */
    public function update(Request $request, string $id = '1'): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_name'           => 'sometimes|string|max:255',
            'business_name_ar'        => 'sometimes|string|max:255',
            'phone'                   => 'sometimes|string|max:30',
            'email'                   => 'sometimes|email|max:255',
            'address'                 => 'sometimes|string|max:500',
            'whatsapp'                => 'sometimes|nullable|string|max:30',
            'facebook'                => 'sometimes|nullable|url|max:255',
            'instagram'               => 'sometimes|nullable|url|max:255',
            'twitter'                 => 'sometimes|nullable|url|max:255',
            'tiktok'                  => 'sometimes|nullable|url|max:255',
            'snapchat'                => 'sometimes|nullable|url|max:255',
            'language'                => 'sometimes|string|size:2',
            'available_languages'     => 'sometimes|array',
            'available_languages.*'   => 'string|size:2',
            'notification_settings'   => 'sometimes|array',
            'logo'                    => 'sometimes|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->except(['logo', '_method']);

            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')
                    ->store('logos/' . tenant()->id, 'public');
            }

            $settings = Setting::updateOrCreate([], $data);

            return response()->json([
                'success' => true,
                'message' => __('Settings saved successfully!'),
                'data'    => $settings->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Tenant/SettingController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to save settings.'),
            ], 500);
        }
    }

    // ── Unused stub methods kept for apiResource compatibility ────────────────

    public function index(): JsonResponse
    {
        return $this->show('1');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->update($request, '1');
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not supported.'], 405);
    }
}
