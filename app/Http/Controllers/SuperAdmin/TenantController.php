<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\TenantSubscription;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class TenantController extends Controller
{
    /**
     * Display a listing of all tenants.
     */
    public function index()
    {
        $tenants = Tenant::with(['domains', 'currentSubscription.plan'])->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:domains,domain',
            'email' => 'nullable|email|max:255',
            'active' => 'boolean',
        ]);

        // Generate email and password for tenant admin
        // Extract subdomain part only for cleaner email: admin@subdomain.test → admin@subdomain
        $domainPart = explode('.', $validated['domain'])[0];
        $generatedEmail = $validated['email'] ?? 'admin@' . $domainPart . '.com';
        $generatedPassword = Str::random(12);

        // Create tenant with UUID
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
        ]);

        // Set custom attributes (stored in data JSON column)
        $tenant->name = $validated['name'];
        $tenant->active = $validated['active'] ?? true;
        $tenant->email = $generatedEmail;
        $tenant->save();

        // Create domain for the tenant
        $tenant->domains()->create([
            'domain' => $validated['domain'],
        ]);

        // Manually trigger database creation and migration
        try {
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
            ]);
        } catch (\Exception $e) {
            // If migration fails, delete the tenant
            $tenant->delete();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tenant database: ' . $e->getMessage()
            ], 500);
        }

        // Run migrations and create admin user for tenant
        $tenant->run(function () use ($validated, $generatedEmail, $generatedPassword) {
            // Migrations will run automatically via Stancl\Tenancy

            // Get or create Admin Tenant role
            $adminRole = Role::firstOrCreate(
                ['name' => 'Admin Tenant'],
                ['description' => 'Administrator with full access']
            );

            // Create admin user for this tenant (no tenant_id needed in tenant DB)
            $adminUser = User::create([
                'name' => $validated['name'] . ' Admin',
                'email' => $generatedEmail,
                'password' => Hash::make($generatedPassword),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => [
                'tenant' => $tenant->load('domains'),
                'credentials' => [
                    'email' => $generatedEmail,
                    'password' => $generatedPassword,
                    'login_url' => 'http://' . $validated['domain'],
                ]
            ]
        ], 201);
    }

    /**
     * Display the specified tenant.
     */
    public function show(string $id)
    {
        $tenant = Tenant::with(['domains', 'settings', 'users'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tenant
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        // Update custom attributes
        if (isset($validated['name'])) {
            $tenant->name = $validated['name'];
        }
        if (isset($validated['active'])) {
            $tenant->active = $validated['active'];
        }
        $tenant->save();

        // Update domain if provided
        if (isset($validated['domain'])) {
            $tenant->domains()->delete();
            $tenant->domains()->create([
                'domain' => $validated['domain'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'data' => $tenant->load('domains')
        ]);
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully'
        ]);
    }

    /**
     * Activate or deactivate tenant.
     */
    public function toggleStatus(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->active = !$tenant->active;
        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Tenant status updated successfully',
            'data' => [
                'id' => $tenant->id,
                'active' => $tenant->active
            ]
        ]);
    }

    /**
     * Get tenant statistics.
     */
    public function statistics(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        // Initialize tenancy to get tenant data
        tenancy()->initialize($tenant);

        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_appointments' => \App\Models\Appointment::count(),
            'pending_appointments' => \App\Models\Appointment::where('status', 'Pending')->count(),
            'confirmed_appointments' => \App\Models\Appointment::where('status', 'Confirmed')->count(),
            'total_invoices' => \App\Models\Invoice::count(),
            'pending_invoices' => \App\Models\Invoice::where('status', 'Pending')->count(),
            'paid_invoices' => \App\Models\Invoice::where('status', 'Paid')->count(),
        ];

        // End tenancy
        tenancy()->end();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Assign subscription to tenant
     */
    public function assignSubscription(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'status' => 'sometimes|in:trial,active,suspended,cancelled',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        // Cancel any existing active subscription
        \App\Models\TenantSubscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        // Create new subscription
        $subscription = \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'status' => $validated['status'] ?? 'trial',
            'trial_ends_at' => isset($validated['trial_days']) ? now()->addDays($validated['trial_days']) : null,
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        \App\Models\ActivityLog::log('assigned_subscription', "Assigned subscription to tenant: {$tenant->name}");

        return response()->json([
            'success' => true,
            'message' => 'Subscription assigned successfully',
            'data' => $subscription->load('plan')
        ]);
    }

    /**
     * Get tenant users
     */
    public function users(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        tenancy()->initialize($tenant);

        $users = \App\Models\User::with('role')->get();

        tenancy()->end();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Reset tenant admin password
     */
    public function resetAdminPassword(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        tenancy()->initialize($tenant);

        $admin = \App\Models\User::where('role_id', 1)->first();

        if (!$admin) {
            tenancy()->end();
            return response()->json([
                'success' => false,
                'message' => 'Admin user not found'
            ], 404);
        }

        $newPassword = \Illuminate\Support\Str::random(12);
        $admin->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $admin->save();

        tenancy()->end();

        \App\Models\ActivityLog::log('reset_password', "Reset admin password for tenant: {$tenant->name}");

        return response()->json([
            'success' => true,
            'message' => 'Admin password reset successfully',
            'data' => [
                'email' => $admin->email,
                'password' => $newPassword
            ]
        ]);
    }

    /**
     * Get tenant subscription details
     */
    public function subscription(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $subscription = \App\Models\TenantSubscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $subscription
        ]);
    }
}
