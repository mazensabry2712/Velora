<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\TenantSubscription;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TenantsExport;

class TenantController extends Controller
{
    /**
     * Display a listing of all tenants.
     */
    public function index()
    {
        $tenants = Tenant::with(['domains', 'currentSubscription.plan', 'subscriptions.plan'])->latest()->get();

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
            'country' => 'nullable|string|max:100',
            'specialty' => 'nullable|string|max:100',
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
        $tenant->country = $validated['country'] ?? null;
        $tenant->specialty = $validated['specialty'] ?? null;
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

            // Get or create Admin Tenant role (normally already seeded by TenantDatabaseSeeder)
            $adminRole = Role::firstOrCreate(
                ['name' => 'Admin Tenant', 'guard_name' => 'web']
            );

            // Create admin user for this tenant (no tenant_id needed in tenant DB)
            $adminUser = User::create([
                'name' => $validated['name'] . ' Admin',
                'email' => $generatedEmail,
                'password' => Hash::make($generatedPassword),
                'email_verified_at' => now(),
            ]);

            $adminUser->assignRole($adminRole);
        });

        ActivityLog::log('created', "Created tenant: {$tenant->name}, domain: {$validated['domain']}");

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
        $tenant = Tenant::with(['domains', 'settings'])->findOrFail($id);

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
        $currentDomain = $tenant->domains()->first()?->domain;

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => [
                'sometimes', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('domains', 'domain')->ignore($currentDomain, 'domain'),
            ],
            'active' => 'sometimes|boolean',
            'country' => 'sometimes|nullable|string|max:100',
            'specialty' => 'sometimes|nullable|string|max:100',
        ]);

        // Update custom attributes
        if (isset($validated['name'])) {
            $tenant->name = $validated['name'];
        }
        if (isset($validated['active'])) {
            $tenant->active = $validated['active'];
        }
        if (array_key_exists('country', $validated)) {
            $tenant->country = $validated['country'];
        }
        if (array_key_exists('specialty', $validated)) {
            $tenant->specialty = $validated['specialty'];
        }
        $tenant->save();

        // Update domain if provided
        if (isset($validated['domain'])) {
            $tenant->domains()->delete();
            $tenant->domains()->create([
                'domain' => $validated['domain'],
            ]);
        }

        ActivityLog::log('updated', "Updated tenant: {$tenant->name}");

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
        $tenantName   = $tenant->name;
        $tenantDomain = $tenant->domains()->first()?->domain ?? 'unknown';

        ActivityLog::log('deleted', "Moved tenant to trash: {$tenantName}, domain: {$tenantDomain}");

        // Wrap in withoutEvents to prevent Stancl's TenantDeleted event from
        // firing the DeleteDatabase pipeline. We only want that on forceDelete().
        Tenant::withoutEvents(function () use ($tenant) {
            $tenant->delete(); // soft delete — only sets deleted_at
        });

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully'
        ]);
    }

    /**
     * Export all tenants to Excel.
     */
    public function exportExcel()
    {
        $filename = 'tenants-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new TenantsExport(), $filename);
    }

    /**
     * Soft-delete ALL active tenants (move them all to trash).
     */
    public function deleteAll()
    {
        $tenants = Tenant::all();

        $count = $tenants->count();

        foreach ($tenants as $tenant) {
            Tenant::withoutEvents(function () use ($tenant) {
                $tenant->delete();
            });
            ActivityLog::log('deleted', "Moved tenant to trash: {$tenant->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'All tenants moved to trash',
            'count'   => $count,
        ]);
    }

    /**
     * List soft-deleted (trashed) tenants.
     */
    public function trash()
    {
        $tenants = Tenant::onlyTrashed()->with('domains')->latest('deleted_at')->get();

        return response()->json([
            'success' => true,
            'data'    => $tenants,
        ]);
    }

    /**
     * Restore a soft-deleted tenant.
     */
    public function restore(string $id)
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);

        // BaseTenant::save() only persists the 'data' JSON column; deleted_at
        // is a first-class column so we bypass Eloquent and update it directly.
        \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $id)
            ->update(['deleted_at' => null]);

        ActivityLog::log('restored', "Restored tenant from trash: {$tenant->name}");

        return response()->json([
            'success' => true,
            'message' => 'Tenant restored successfully',
            'data'    => $tenant->fresh()->load('domains'),
        ]);
    }

    /**
     * Restore ALL trashed tenants.
     */
    public function restoreAll()
    {
        $trashed = Tenant::onlyTrashed()->get();

        $count = $trashed->count();

        foreach ($trashed as $tenant) {
            \Illuminate\Support\Facades\DB::table('tenants')
                ->where('id', $tenant->getTenantKey())
                ->update(['deleted_at' => null]);

            ActivityLog::log('restored', "Restored tenant from trash: {$tenant->name}");
        }

        return response()->json([
            'success' => true,
            'message' => 'All trashed tenants restored',
            'count'   => $count,
        ]);
    }

    /**
     * Permanently delete ALL trashed tenants.
     */
    public function forceDeleteAll()
    {
        $trashed = Tenant::onlyTrashed()->get();

        foreach ($trashed as $tenant) {
            $tenantName   = $tenant->name;
            $tenantDomain = $tenant->domains()->first()?->domain ?? 'unknown';
            ActivityLog::log('force_deleted', "Permanently deleted tenant: {$tenantName}, domain: {$tenantDomain}");
            $this->safeForceDelete($tenant);
        }

        return response()->json([
            'success' => true,
            'message' => 'All trashed tenants permanently deleted',
            'count'   => $trashed->count(),
        ]);
    }

    /**
     * Permanently delete a trashed tenant.
     */
    public function forceDelete(string $id)
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        $tenantName   = $tenant->name;
        $tenantDomain = $tenant->domains()->first()?->domain ?? 'unknown';

        ActivityLog::log('force_deleted', "Permanently deleted tenant: {$tenantName}, domain: {$tenantDomain}");

        $this->safeForceDelete($tenant);

        return response()->json([
            'success' => true,
            'message' => 'Tenant permanently deleted',
        ]);
    }

    /**
     * Safely force-delete a tenant.
     * If the tenant database doesn't exist (e.g. test data or failed provisioning),
     * MySQL error 1008 is raised by the TenantDeleted → DeleteDatabase event.
     * In that case we fall back to withoutEvents() so the Eloquent record is still removed.
     */
    private function safeForceDelete(Tenant $tenant): void
    {
        try {
            $tenant->forceDelete(); // fires TenantDeleted → drops DB
        } catch (\Illuminate\Database\QueryException $e) {
            // 1008 = "Can't drop database; database doesn't exist"
            if ($e->getCode() === '1008' || str_contains($e->getMessage(), "Can't drop database")) {
                Tenant::withoutEvents(fn () => $tenant->forceDelete());
            } else {
                throw $e;
            }
        }
    }

    /**
     * Activate or deactivate tenant.
     */
    public function toggleStatus(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->active = !$tenant->active;
        $tenant->save();

        $status = $tenant->active ? 'activated' : 'deactivated';
        ActivityLog::log('toggle_status', "Tenant {$status}: {$tenant->name}");

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

        $stats = [
            'total_users'            => 0,
            'total_appointments'     => 0,
            'pending_appointments'   => 0,
            'confirmed_appointments' => 0,
            'total_invoices'         => 0,
            'pending_invoices'       => 0,
            'paid_invoices'          => 0,
        ];

        $connName = 'tenant_stats_' . $tenant->getTenantKey();

        try {
            // Build a throwaway DB connection using Stancl's own connection config.
            // This avoids tenancy()->initialize() which triggers Filesystem/Cache bootstrappers
            // that can crash in certain environments.
            $connConfig = $tenant->database()->connection();

            Config::set("database.connections.{$connName}", $connConfig);

            $db = \Illuminate\Support\Facades\DB::connection($connName);

            $stats = [
                'total_users'            => $this->safeCount($db, 'users'),
                'total_appointments'     => $this->safeCount($db, 'appointments'),
                'pending_appointments'   => $this->safeCount($db, 'appointments', ['status' => 'Pending']),
                'confirmed_appointments' => $this->safeCount($db, 'appointments', ['status' => 'Confirmed']),
                'total_invoices'         => $this->safeCount($db, 'invoices'),
                'pending_invoices'       => $this->safeCount($db, 'invoices', ['status' => 'Pending']),
                'paid_invoices'          => $this->safeCount($db, 'invoices', ['status' => 'Paid']),
            ];
        } catch (\Throwable $e) {
            // DB inaccessible or tables missing — return zeros
        } finally {
            \Illuminate\Support\Facades\DB::purge($connName);
            Config::offsetUnset("database.connections.{$connName}");
        }

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /** Count rows safely — returns 0 if the table doesn't exist yet. */
    private function safeCount(\Illuminate\Database\ConnectionInterface $db, string $table, array $where = []): int
    {
        try {
            $query = $db->table($table);
            foreach ($where as $col => $val) {
                $query->where($col, $val);
            }
            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Assign subscription to tenant
     */
    public function assignSubscription(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'status'               => 'sometimes|in:trial,active,suspended,cancelled',
            'starts_at'            => 'nullable|date',
            'ends_at'              => 'nullable|date|after:starts_at',
            'trial_days'           => 'nullable|integer|min:0',
            'amount_paid'          => 'nullable|numeric|min:0|max:99999',
        ]);

        // Cancel any existing active subscription
        \App\Models\TenantSubscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        // Create new subscription
        $subscription = \App\Models\TenantSubscription::create([
            'tenant_id'            => $tenant->id,
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'status'               => $validated['status'] ?? 'trial',
            'trial_ends_at'        => isset($validated['trial_days']) ? now()->addDays($validated['trial_days']) : null,
            'starts_at'            => $validated['starts_at'] ?? now(),
            'ends_at'              => $validated['ends_at'] ?? null,
            'amount_paid'          => $validated['amount_paid'] ?? 0,
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

        $admin = \App\Models\User::role('Admin Tenant')->first();

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
