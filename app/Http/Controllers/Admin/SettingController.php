<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $tenant       = tenant();
        $settingsModel = Setting::where('tenant_id', $tenant->id)->first();
        $settings     = $settingsModel ? $settingsModel->toArray() : [];

        return view('admin.settings.index', compact('settings'));
    }

    public function save(SaveSettingsRequest $request): JsonResponse
    {
        try {
            $tenant = tenant();
            $data   = $request->validated();

            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('logos/' . $tenant->id, 'public');
            }

            $settings = Setting::updateOrCreate(['tenant_id' => $tenant->id], $data);

            return response()->json([
                'success' => true,
                'message' => __('Settings saved successfully!'),
                'data'    => $settings,
            ]);
        } catch (\Exception $e) {
            Log::error('saveSettings: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
