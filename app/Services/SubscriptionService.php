<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\User;

class SubscriptionService
{
    /**
     * Resolve the canonical central database connection used by subscription data.
     */
    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', 'mysql');
    }

    /**
     * Get current tenant's subscription info
     */
    public function getSubscriptionInfo()
    {
        $tenantId = tenant('id');

        if (!$tenantId) {
            return null;
        }

        try {
            $subscription = DB::connection($this->centralConnection())->table('tenant_subscriptions')
                ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->where('tenant_subscriptions.tenant_id', $tenantId)
                ->whereIn('tenant_subscriptions.status', ['active', 'trial'])
                ->select(
                    'subscription_plans.name as plan_name',
                    'subscription_plans.max_users',
                    'subscription_plans.max_appointments',
                    'subscription_plans.storage_limit',
                    'subscription_plans.features',
                    'subscription_plans.price',
                    'subscription_plans.billing_cycle',
                    'tenant_subscriptions.status',
                    'tenant_subscriptions.starts_at',
                    'tenant_subscriptions.ends_at',
                    'tenant_subscriptions.trial_ends_at',
                    'tenant_subscriptions.grace_ends_at',
                    'tenant_subscriptions.subscription_plan_id',
                    'tenant_subscriptions.stripe_customer_id',
                    'tenant_subscriptions.trial_extended'
                )
                // CASE is supported by both MySQL and SQLite; FIELD() is MySQL-only.
                ->orderByRaw("CASE WHEN tenant_subscriptions.status = 'active' THEN 0 WHEN tenant_subscriptions.status = 'trial' THEN 1 ELSE 2 END")
                ->orderByDesc('tenant_subscriptions.created_at')
                ->first();

            if (!$subscription) {
                return null;
            }

            $usage = $this->calculateUsage();

            $daysRemaining = 0;
            if ($subscription->status === 'trial' && $subscription->trial_ends_at) {
                $daysRemaining = max(0, now()->diffInDays($subscription->trial_ends_at, false));
            } elseif ($subscription->ends_at) {
                $daysRemaining = max(0, now()->diffInDays($subscription->ends_at, false));
            }

            return [
                'plan_name' => $subscription->plan_name,
                'plan_id' => $subscription->subscription_plan_id,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'billing_cycle' => $subscription->billing_cycle,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'grace_ends_at' => $subscription->grace_ends_at,
                'days_remaining' => (int) $daysRemaining,
                'stripe_customer_id' => $subscription->stripe_customer_id,
                'trial_extended' => (bool) $subscription->trial_extended,
                'limits' => [
                    'users' => [
                        'max' => $subscription->max_users == -1 ? __('Unlimited') : $subscription->max_users,
                        'current' => $usage['users'],
                        'percentage' => $this->calculatePercentage($usage['users'], $subscription->max_users)
                    ],
                    'appointments' => [
                        'max' => $subscription->max_appointments == -1 ? __('Unlimited') : $subscription->max_appointments,
                        'current' => $usage['appointments'],
                        'percentage' => $this->calculatePercentage($usage['appointments'], $subscription->max_appointments)
                    ],
                    'storage' => [
                        'max' => $subscription->storage_limit == -1 ? __('Unlimited') : $subscription->storage_limit . ' GB',
                        'current' => $usage['storage'],
                        'percentage' => 0
                    ]
                ],
                'features' => json_decode($subscription->features ?? '[]', true),
                'usage' => $usage
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get subscription info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate current usage
     */
    public function calculateUsage()
    {
        $startOfMonth = now()->startOfMonth();
        $startOfNextMonth = now()->startOfMonth()->addMonth();

        return [
            'users' => User::count(),
            'appointments' => Appointment::where('created_at', '>=', $startOfMonth)
                ->where('created_at', '<', $startOfNextMonth)
                ->count(),
            'appointments_total' => Appointment::count(),
            'storage' => 0
        ];
    }

    /**
     * Calculate percentage
     */
    private function calculatePercentage($current, $max)
    {
        if ($max == -1) {
            return 0;
        }

        if ($max == 0) {
            return 100;
        }

        return min(100, round(($current / $max) * 100));
    }

    /**
     * Check if can perform action
     */
    public function canPerformAction($action)
    {
        $info = $this->getSubscriptionInfo();

        if (!$info) {
            return [
                'allowed' => false,
                'message' => __('No active subscription found.')
            ];
        }

        switch ($action) {
            case 'create_user':
                return $this->checkUsersLimit($info);
            case 'create_appointment':
                return $this->checkAppointmentsLimit($info);
            default:
                return ['allowed' => true];
        }
    }

    /**
     * Check users limit
     */
    private function checkUsersLimit($info)
    {
        $current = $info['limits']['users']['current'];
        $max = $info['limits']['users']['max'];

        if ($max === __('Unlimited')) {
            return ['allowed' => true];
        }

        if ($current >= $max) {
            return [
                'allowed' => false,
                'message' => __('You have reached your plan\'s user limit (:max users).', ['max' => $max]),
                'upgrade_required' => true
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check appointments limit
     */
    private function checkAppointmentsLimit($info)
    {
        $current = $info['limits']['appointments']['current'];
        $max = $info['limits']['appointments']['max'];

        if ($max === __('Unlimited')) {
            return ['allowed' => true];
        }

        if ($current >= $max) {
            return [
                'allowed' => false,
                'message' => __('You have reached your plan\'s monthly appointments limit (:max).', ['max' => $max]),
                'upgrade_required' => true
            ];
        }

        if ($current >= ($max * 0.9)) {
            return [
                'allowed' => true,
                'warning' => __('You are approaching your monthly appointments limit (:current/:max).', [
                    'current' => $current,
                    'max' => $max
                ])
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Get available upgrade plans
     */
    public function getAvailableUpgrades()
    {
        $currentInfo = $this->getSubscriptionInfo();

        try {
            $query = DB::connection($this->centralConnection())->table('subscription_plans')
                ->where('is_active', true)
                ->orderBy('price', 'asc');

            if ($currentInfo && isset($currentInfo['plan_id'])) {
                $query->where('id', '!=', $currentInfo['plan_id']);
            }

            $plans = $query->get();

            return $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'billing_cycle' => $plan->billing_cycle,
                    'max_users' => $plan->max_users,
                    'max_appointments' => $plan->max_appointments,
                    'features' => json_decode($plan->features ?? '[]', true),
                    'is_popular' => $plan->is_popular,
                    'stripe_price_id' => $plan->stripe_price_id ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Failed to get available upgrades: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get billing / subscription history for the current tenant, most recent first.
     */
    public function getInvoices(int $limit = 20)
    {
        $tenantId = tenant('id');

        if (!$tenantId) {
            return collect();
        }

        try {
            return DB::connection($this->centralConnection())->table('tenant_subscriptions')
                ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->where('tenant_subscriptions.tenant_id', $tenantId)
                ->select(
                    'tenant_subscriptions.*',
                    'subscription_plans.name as plan_name'
                )
                ->orderByDesc('tenant_subscriptions.created_at')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Failed to get invoices: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Check if feature is available in current plan
     */
    public function hasFeature($featureName)
    {
        $info = $this->getSubscriptionInfo();

        if (!$info || !isset($info['features'])) {
            return false;
        }

        return in_array($featureName, $info['features']);
    }

    /**
     * Log usage for analytics
     */
    public function logUsage($type, $details = [])
    {
        try {
            DB::connection($this->centralConnection())->table('usage_logs')->insert([
                'tenant_id' => tenant('id'),
                'type' => $type,
                'details' => json_encode($details),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log usage: ' . $e->getMessage());
        }
    }
}
