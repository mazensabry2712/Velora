<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use App\Models\Tenant;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    /**
     * Display a listing of notifications
     */
    public function index()
    {
        $notifications = SystemNotification::with('creator')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Store a new notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,danger',
            'target' => 'required|in:all,specific',
            'tenant_ids' => 'required_if:target,specific|array',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['created_by'] = auth()->guard('web')->id();
        $validated['is_sent'] = false;

        $notification = SystemNotification::create($validated);

        // If not scheduled, send immediately
        if (!$validated['scheduled_at']) {
            $this->sendNotification($notification);
        }

        ActivityLog::log('created', "Created system notification: {$notification->title}");

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    /**
     * Display a specific notification
     */
    public function show(string $id)
    {
        $notification = SystemNotification::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(string $id)
    {
        $notification = SystemNotification::findOrFail($id);

        ActivityLog::log('deleted', "Deleted system notification: {$notification->title}");

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Send notification immediately
     */
    public function send(string $id)
    {
        $notification = SystemNotification::findOrFail($id);

        if ($notification->is_sent) {
            return response()->json([
                'success' => false,
                'message' => 'Notification already sent'
            ], 422);
        }

        $this->sendNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully'
        ]);
    }

    /**
     * Send notification to target tenants
     */
    protected function sendNotification(SystemNotification $notification)
    {
        if ($notification->target === 'all') {
            $tenants = Tenant::all();
        } else {
            $tenants = Tenant::whereIn('id', $notification->tenant_ids)->get();
        }

        // Here you would implement the actual notification sending
        // For now, we'll just mark it as sent
        // In a real app, you might use email, database notifications, etc.

        $notification->markAsSent();

        ActivityLog::log('sent', "Sent system notification: {$notification->title} to " . $tenants->count() . " tenants");
    }
}
