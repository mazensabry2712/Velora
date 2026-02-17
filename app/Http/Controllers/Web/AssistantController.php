<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Role;
use App\Mail\WelcomeAssistantMail;

class AssistantController extends Controller
{
    /**
     * Get all available permissions
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'manage_appointments' => [
                'name' => __('Manage Appointments'),
                'description' => __('View, create, edit and delete appointments'),
            ],
            'manage_queue' => [
                'name' => __('Manage Queue'),
                'description' => __('Manage waiting queue'),
            ],
            'manage_staff' => [
                'name' => __('Manage Staff'),
                'description' => __('Add and manage staff members'),
            ],
            'manage_customers' => [
                'name' => __('Manage Customers'),
                'description' => __('View and manage customers'),
            ],
            'view_reports' => [
                'name' => __('View Reports'),
                'description' => __('View reports and statistics'),
            ],
            'manage_settings' => [
                'name' => __('Manage Settings'),
                'description' => __('Manage services, time slots and working days'),
            ],
            'manage_assistants' => [
                'name' => __('Manage Assistants'),
                'description' => __('Add and manage assistants'),
            ],
        ];
    }

    /**
     * Get all assistants
     */
    public function index()
    {
        $assistantRole = Role::where('name', 'Assistant')->first();

        $assistants = User::where('role_id', $assistantRole?->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assistants,
        ]);
    }

    /**
     * Store a new assistant
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(self::getAvailablePermissions())),
        ]);

        // Get or create Assistant role
        $assistantRole = Role::firstOrCreate(
            ['name' => 'Assistant'],
            ['permissions' => []]
        );

        $plainPassword = $request->password;

        $assistant = User::create([
            'role_id' => $assistantRole->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($plainPassword),
            'permissions' => $request->permissions,
        ]);

        // Send welcome email with credentials
        try {
            Mail::to($assistant->email)->send(new WelcomeAssistantMail(
                $assistant,
                $plainPassword,
                tenant()
            ));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send welcome email to assistant: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => __('Assistant created successfully'),
            'data' => $assistant,
        ], 201);
    }

    /**
     * Show assistant details
     */
    public function show($id)
    {
        $assistant = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $assistant,
        ]);
    }

    /**
     * Update assistant
     */
    public function update(Request $request, $id)
    {
        $assistant = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|in:' . implode(',', array_keys(self::getAvailablePermissions())),
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'permissions' => $request->permissions,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $assistant->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Assistant updated successfully'),
            'data' => $assistant,
        ]);
    }

    /**
     * Delete assistant
     */
    public function destroy($id)
    {
        $assistant = User::findOrFail($id);

        // Make sure we're deleting an assistant
        if (!$assistant->isAssistant()) {
            return response()->json([
                'success' => false,
                'message' => __('This user is not an assistant'),
            ], 400);
        }

        $assistant->delete();

        return response()->json([
            'success' => true,
            'message' => __('Assistant deleted successfully'),
        ]);
    }
}
