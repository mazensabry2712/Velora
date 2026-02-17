<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * Display a listing of all tenants.
     */
    public function index()
    {
        $tenants = Tenant::with('domains')->latest()->paginate(20);

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
            'domain' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        // Create tenant with UUID
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
        ]);

        // Set custom attributes (stored in data JSON column)
        $tenant->name = $validated['name'];
        $tenant->active = $validated['active'] ?? true;
        $tenant->save();

        // Create domain for the tenant
        $tenant->domains()->create([
            'domain' => $validated['domain'],
        ]);

        // Run migrations for tenant database
        $tenant->run(function () use ($tenant) {
            // Migrations will run automatically via stancl/tenancy
        });

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => $tenant->load('domains')
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
}
