<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Models\UpgradeRequest;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\UpgradeApprovedMail;
use App\Mail\UpgradeRejectedMail;

class SuperAdminController extends Controller
{
    public function promoCodes()
    {
        $promoCodes = PromoCode::query()->latest()->paginate(20);
        return view('super-admin.promo-codes.index', compact('promoCodes'));
    }

    public function storePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $code = strtoupper(trim($validated['code']));

        if (PromoCode::whereRaw('UPPER(code) = ?', [$code])->exists()) {
            return back()->withErrors(['code' => 'This promo code already exists.'])->withInput();
        }

        if ($validated['discount_type'] === 'percent' && (float) $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discount cannot exceed 100.'])->withInput();
        }

        PromoCode::create([
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'max_uses' => $validated['max_uses'] ?? null,
            'used_count' => 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('super-admin.promo-codes.index')->with('success', 'Promo code created successfully.');
    }

    public function togglePromoCode($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->update(['is_active' => ! $promoCode->is_active]);
        return redirect()->route('super-admin.promo-codes.index')->with('success', 'Promo code status updated successfully.');
    }

    public function destroyPromoCode($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->delete();
        return redirect()->route('super-admin.promo-codes.index')->with('success', 'Promo code deleted successfully.');
    }

    public function upgradeRequests(Request $request)
    {
        $statusFilter = $request->get('status', 'all');
        $query = UpgradeRequest::with(['currentPlan', 'requestedPlan'])->orderBy('created_at', 'desc');
        if ($statusFilter !== 'all') $query->where('status', $statusFilter);
        $requests = $query->paginate(15)->withQueryString();
        $counts = ['total' => UpgradeRequest::count(), 'pending' => UpgradeRequest::where('status', 'pending')->count(), 'approved' => UpgradeRequest::where('status', 'approved')->count(), 'rejected' => UpgradeRequest::where('status', 'rejected')->count()];
        return view('super-admin.upgrade-requests.index', compact('requests', 'counts', 'statusFilter'));
    }

    public function showUpgradeRequest($id)
    {
        $request = UpgradeRequest::with(['currentPlan', 'requestedPlan'])->findOrFail($id);
        $tenant = Tenant::find($request->tenant_id);
        $subscription = TenantSubscription::where('tenant_id', $request->tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();
        $usage = $this->getTenantUsage($request->tenant_id);
        return view('super-admin.upgrade-requests.show', compact('request', 'tenant', 'subscription', 'usage'));
    }

    public function approveUpgrade(Request $request, $id)
    {
        $validated = $request->validate(['admin_notes' => 'nullable|string', 'start_date' => 'nullable|date']);
        DB::beginTransaction();
        try {
            $upgradeRequest = UpgradeRequest::findOrFail($id);
            $upgradeRequest->update(['status' => 'approved', 'admin_notes' => $validated['admin_notes'] ?? null, 'processed_at' => now(), 'processed_by' => auth()->id()]);
            $currentSubscription = TenantSubscription::where('tenant_id', $upgradeRequest->tenant_id)->whereIn('status', ['active', 'trial'])->latest()->first();
            if ($currentSubscription) $currentSubscription->update(['status' => 'expired', 'ends_at' => now(), 'trial_ends_at' => $currentSubscription->status === 'trial' ? now() : $currentSubscription->trial_ends_at]);
            $startDate = isset($validated['start_date']) ? \Carbon\Carbon::parse($validated['start_date']) : now();
            $newPlan = SubscriptionPlan::findOrFail($upgradeRequest->requested_plan_id);
            $endDate = match($newPlan->billing_cycle ?? 'monthly') { 'yearly' => $startDate->copy()->addYear(), 'weekly' => $startDate->copy()->addWeek(), default => $startDate->copy()->addMonth() };
            $newSubscription = TenantSubscription::create(['tenant_id' => $upgradeRequest->tenant_id, 'subscription_plan_id' => $upgradeRequest->requested_plan_id, 'status' => 'active', 'starts_at' => $startDate, 'ends_at' => $endDate, 'trial_ends_at' => null]);
            DB::commit();
            try { Mail::to($upgradeRequest->requested_by_email)->send(new UpgradeApprovedMail($upgradeRequest->fresh(['currentPlan', 'requestedPlan']), $newSubscription)); } catch (\Exception $e) { Log::warning('Failed to send upgrade approval email: ' . $e->getMessage()); }
            return redirect()->route('super-admin.upgrade-requests')->with('success', 'Upgrade request approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    public function rejectUpgrade(Request $request, $id)
    {
        $validated = $request->validate(['admin_notes' => 'required|string']);
        try {
            $upgradeRequest = UpgradeRequest::findOrFail($id);
            $upgradeRequest->update(['status' => 'rejected', 'admin_notes' => $validated['admin_notes'], 'processed_at' => now(), 'processed_by' => auth()->id()]);
            try { Mail::to($upgradeRequest->requested_by_email)->send(new UpgradeRejectedMail($upgradeRequest->fresh(['currentPlan', 'requestedPlan']))); } catch (\Exception $e) { Log::warning('Failed to send upgrade rejection email: ' . $e->getMessage()); }
            return redirect()->route('super-admin.upgrade-requests')->with('success', 'Upgrade request rejected.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject request: ' . $e->getMessage());
        }
    }

    private function getTenantUsage($tenantId)
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) return null;
            tenancy()->initialize($tenant);
            $usage = ['total_users' => DB::table('users')->count(), 'total_appointments' => DB::table('appointments')->count(), 'appointments_this_month' => DB::table('appointments')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count()];
            tenancy()->end();
            return $usage;
        } catch (\Exception $e) { return null; }
    }

    public function dashboard()
    {
        $recentActivitiesRaw = \App\Models\ActivityLog::select('id', 'action', 'description', 'created_at', 'user_id')->with(['user:id,name'])->latest()->take(10)->get();
        $recentActivities = $recentActivitiesRaw->map(function ($a) { $action = $a->action ?? 'edit'; $type = in_array($action, ['created', 'login', 'register', 'add']) ? 'add' : ($action === 'deleted' ? 'delete' : 'edit'); return ['id' => $a->id, 'type' => $type, 'message' => $a->description ?? __('Activity'), 'user' => $a->user?->name ?? 'System', 'time' => $a->created_at->toISOString()]; })->values()->toArray();
        $allTenants = Tenant::latest()->get();
        $stats = ['total_tenants' => $allTenants->count(), 'active_tenants' => $allTenants->filter(fn($t) => $t->active)->count(), 'paid_tenants' => TenantSubscription::where('status', 'active')->distinct('tenant_id')->count(), 'trial_tenants' => TenantSubscription::where('status', 'trial')->distinct('tenant_id')->count(), 'inactive_tenants' => $allTenants->filter(fn($t) => !$t->active)->count(), 'tenants_this_month' => Tenant::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(), 'pending_upgrade_requests' => UpgradeRequest::where('status', 'pending')->count(), 'recent_activities' => $recentActivities, 'activity_today' => \App\Models\ActivityLog::whereDate('created_at', today())->count(), 'activity_week' => \App\Models\ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(), 'recent_tenants' => $allTenants->map(fn($tenant) => ['id' => $tenant->id, 'name' => $tenant->name ?? 'N/A', 'subdomain' => $tenant->id, 'is_active' => $tenant->active, 'created_at' => $tenant->created_at->toISOString()])->values()];
        return view('super-admin.dashboard', compact('stats'));
    }

    public function getDashboardData()
    {
        $data = ['total_tenants' => Tenant::count(), 'active_tenants' => TenantSubscription::whereIn('status', ['active', 'trial'])->distinct('tenant_id')->count(), 'paid_tenants' => TenantSubscription::where('status', 'active')->distinct('tenant_id')->count(), 'trial_tenants' => TenantSubscription::where('status', 'trial')->distinct('tenant_id')->count(), 'inactive_tenants' => Tenant::whereDoesntHave('subscriptions', fn($q) => $q->whereIn('status', ['active', 'trial']))->count(), 'tenants_this_month' => Tenant::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(), 'pending_upgrade_requests' => UpgradeRequest::where('status', 'pending')->count(), 'recent_tenants' => Tenant::with('subscriptions')->orderBy('created_at', 'desc')->limit(10)->get()->map(fn($tenant) => ['id' => $tenant->id, 'name' => $tenant->name ?? 'N/A', 'subdomain' => $tenant->id, 'is_active' => $tenant->subscriptions->whereIn('status', ['active', 'trial'])->count() > 0, 'created_at' => $tenant->created_at->toISOString()])];
        return response()->json(['success' => true, 'data' => $data]);
    }
}
