<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * Display all settings grouped by category
     */
    public function index()
    {
        $settings = SystemSetting::all()->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Get a specific setting
     */
    public function show(string $key)
    {
        $value = SystemSetting::get($key);

        return response()->json([
            'success' => true,
            'data' => $value
        ]);
    }

    /**
     * Update settings (bulk)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'sometimes|in:string,number,boolean,json',
            'settings.*.group' => 'sometimes|string',
        ]);

        foreach ($validated['settings'] as $setting) {
            SystemSetting::set(
                $setting['key'],
                $setting['value'],
                $setting['type'] ?? 'string',
                $setting['group'] ?? 'general'
            );
        }

        ActivityLog::log('updated', 'Updated system settings');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Update or create a single setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:system_settings,key',
            'value' => 'required',
            'type' => 'required|in:string,number,boolean,json',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $setting = SystemSetting::create($validated);

        ActivityLog::log('created', "Created system setting: {$setting->key}");

        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => $setting
        ], 201);
    }

    /**
     * Delete a setting
     */
    public function destroy(string $key)
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();

        ActivityLog::log('deleted', "Deleted system setting: {$key}");

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully'
        ]);
    }
}
