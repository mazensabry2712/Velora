<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckSubscriptionLimits
{
    /**
     * Handle an incoming request to check subscription limits
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $limitType (users|appointments|storage)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $limitType = null)
    {
        // Skip for Super Admin
        if (auth()->check() && auth()->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Get current tenant
        $tenantId = tenant('id');

        if (!$tenantId) {
            return $next($request);
        }

        // Get subscription info from central database
        $subscription = DB::connection('mysql')->table('tenant_subscriptions')
            ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('tenant_subscriptions.tenant_id', $tenantId)
            ->where('tenant_subscriptions.status', 'active')
            ->select(
                'subscription_plans.max_users',
                'subscription_plans.max_appointments',
                'subscription_plans.storage_limit',
                'tenant_subscriptions.ends_at',
                'tenant_subscriptions.status'
            )
            ->first();

        // If no subscription, deny access
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => __('No active subscription found. Please contact support.'),
                'error' => 'SUBSCRIPTION_NOT_FOUND'
            ], 403);
        }

        // Check if subscription is expired
        if ($subscription->ends_at && now()->parse($subscription->ends_at)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => __('Your subscription has expired. Please renew to continue.'),
                'error' => 'SUBSCRIPTION_EXPIRED'
            ], 403);
        }

        // Check specific limit type
        if ($limitType) {
            $limitCheck = $this->checkLimit($limitType, $subscription);

            if (!$limitCheck['allowed']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $limitCheck['message'],
                        'error' => $limitCheck['error'],
                        'current' => $limitCheck['current'],
                        'max' => $limitCheck['max']
                    ], 403);
                }

                return redirect()->back()->with('error', $limitCheck['message']);
            }
        }

        return $next($request);
    }

    /**
     * Check specific limit
     */
    private function checkLimit($type, $subscription)
    {
        switch ($type) {
            case 'users':
                return $this->checkUsersLimit($subscription);
            case 'appointments':
                return $this->checkAppointmentsLimit($subscription);
            case 'storage':
                return $this->checkStorageLimit($subscription);
            default:
                return ['allowed' => true];
        }
    }

    /**
     * Check users limit
     */
    private function checkUsersLimit($subscription)
    {
        $currentUsers = \App\Models\User::count();
        $maxUsers = $subscription->max_users;

        if ($maxUsers == -1) { // Unlimited
            return ['allowed' => true];
        }

        if ($currentUsers >= $maxUsers) {
            return [
                'allowed' => false,
                'message' => __('You have reached your plan\'s user limit (:max users). Please upgrade your plan.', ['max' => $maxUsers]),
                'error' => 'USERS_LIMIT_REACHED',
                'current' => $currentUsers,
                'max' => $maxUsers
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check appointments limit
     */
    private function checkAppointmentsLimit($subscription)
    {
        $currentMonth = now()->format('Y-m');
        $currentAppointments = \App\Models\Appointment::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->count();
        $maxAppointments = $subscription->max_appointments;

        if ($maxAppointments == -1) { // Unlimited
            return ['allowed' => true];
        }

        if ($currentAppointments >= $maxAppointments) {
            return [
                'allowed' => false,
                'message' => __('You have reached your plan\'s monthly appointments limit (:max appointments). Please upgrade your plan.', ['max' => $maxAppointments]),
                'error' => 'APPOINTMENTS_LIMIT_REACHED',
                'current' => $currentAppointments,
                'max' => $maxAppointments
            ];
        }

        // Warning at 90%
        if ($currentAppointments >= ($maxAppointments * 0.9)) {
            session()->flash('warning', __('You are approaching your monthly appointments limit (:current/:max).', [
                'current' => $currentAppointments,
                'max' => $maxAppointments
            ]));
        }

        return ['allowed' => true];
    }

    /**
     * Check storage limit (for future use)
     */
    private function checkStorageLimit($subscription)
    {
        // Storage check - implement when needed
        return ['allowed' => true];
    }
}
