<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class EloquentSubscriptionReader implements SubscriptionReader
{
    private const CENTRAL_CONNECTION = 'mysql';

    public function current(): ?array
    {
        $tenantId = tenant('id');
        if (! $tenantId) return null;

        try {
            $subscription = DB::connection(self::CENTRAL_CONNECTION)
                ->table('tenant_subscriptions')
                ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->where('tenant_subscriptions.tenant_id', $tenantId)
                ->whereIn('tenant_subscriptions.status', ['active', 'trial'])
                ->select('subscription_plans.name as plan_name','subscription_plans.max_users','subscription_plans.max_appointments','subscription_plans.storage_limit','subscription_plans.features','subscription_plans.price','subscription_plans.billing_cycle','tenant_subscriptions.status','tenant_subscriptions.starts_at','tenant_subscriptions.ends_at','tenant_subscriptions.trial_ends_at','tenant_subscriptions.grace_ends_at','tenant_subscriptions.subscription_plan_id','tenant_subscriptions.stripe_customer_id','tenant_subscriptions.trial_extended')
                ->orderByRaw("CASE WHEN tenant_subscriptions.status = 'active' THEN 0 WHEN tenant_subscriptions.status = 'trial' THEN 1 ELSE 2 END")
                ->orderByDesc('tenant_subscriptions.created_at')
                ->first();

            if (! $subscription) return null;

            $usage = $this->usage();
            $maxUsers = (int) $subscription->max_users;
            $maxAppointments = (int) $subscription->max_appointments;
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
                    'users' => ['max' => $maxUsers === -1 ? __('Unlimited') : $maxUsers, 'current' => $usage['users'], 'percentage' => $this->percentage($usage['users'], $maxUsers)],
                    'appointments' => ['max' => $maxAppointments === -1 ? __('Unlimited') : $maxAppointments, 'current' => $usage['appointments'], 'percentage' => $this->percentage($usage['appointments'], $maxAppointments)],
                    'storage' => ['max' => (int) $subscription->storage_limit === -1 ? __('Unlimited') : $subscription->storage_limit . ' GB', 'current' => $usage['storage'], 'percentage' => 0],
                ],
                'features' => json_decode($subscription->features ?? '[]', true),
                'usage' => $usage,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to get subscription info: ' . $e->getMessage());
            return null;
        }
    }

    public function usage(): array
    {
        $start = now()->startOfMonth();
        $next = now()->startOfMonth()->addMonth();
        return [
            'users' => User::count(),
            'appointments' => Appointment::query()->where('created_at', '>=', $start)->where('created_at', '<', $next)->count(),
            'appointments_total' => Appointment::count(),
            'storage' => 0,
        ];
    }

    public function availableUpgrades(): array
    {
        try {
            $current = $this->current();
            $query = DB::connection(self::CENTRAL_CONNECTION)->table('subscription_plans')->where('is_active', true)->orderBy('price');
            if ($current && isset($current['plan_id'])) $query->where('id', '!=', $current['plan_id']);
            return $query->get()->map(static fn (object $plan): array => [
                'id' => $plan->id, 'name' => $plan->name, 'price' => $plan->price, 'billing_cycle' => $plan->billing_cycle,
                'max_users' => $plan->max_users, 'max_appointments' => $plan->max_appointments,
                'features' => json_decode($plan->features ?? '[]', true), 'is_popular' => $plan->is_popular,
                'stripe_price_id' => $plan->stripe_price_id ?? null,
            ])->all();
        } catch (\Throwable $e) {
            Log::error('Failed to get available upgrades: ' . $e->getMessage());
            return [];
        }
    }

    public function invoices(int $limit = 20): array
    {
        $tenantId = tenant('id');
        if (! $tenantId) return [];
        try {
            return DB::connection(self::CENTRAL_CONNECTION)->table('tenant_subscriptions')->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')->where('tenant_subscriptions.tenant_id', $tenantId)->select('tenant_subscriptions.*', 'subscription_plans.name as plan_name')->orderByDesc('tenant_subscriptions.created_at')->limit($limit)->get()->all();
        } catch (\Throwable $e) {
            Log::error('Failed to get invoices: ' . $e->getMessage());
            return [];
        }
    }

    public function checkLimit(string $action): array
    {
        $info = $this->current();
        if (! $info) return ['allowed' => false, 'message' => __('No active subscription found.')];
        return match ($action) {
            'create_user' => $this->checkLimitValue($info['limits']['users']['current'], $info['limits']['users']['max'], 'user'),
            'create_appointment' => $this->checkAppointmentLimit($info),
            default => ['allowed' => true],
        };
    }

    public function hasFeature(string $feature): bool
    {
        $info = $this->current();
        return $info !== null && isset($info['features']) && in_array($feature, $info['features'], true);
    }

    private function checkLimitValue(int $current, int|string $max, string $resource): array
    {
        if ($max === __('Unlimited')) return ['allowed' => true];
        if ($current >= (int) $max) return ['allowed' => false, 'message' => __('You have reached your plan\'s :resource limit (:max).', ['resource' => $resource, 'max' => $max]), 'upgrade_required' => true];
        return ['allowed' => true];
    }

    private function checkAppointmentLimit(array $info): array
    {
        $current = (int) $info['limits']['appointments']['current'];
        $max = $info['limits']['appointments']['max'];
        if ($max === __('Unlimited')) return ['allowed' => true];
        if ($current >= (int) $max) return ['allowed' => false, 'message' => __('You have reached your plan\'s monthly appointments limit (:max).', ['max' => $max]), 'upgrade_required' => true];
        if ($current >= ((int) $max * 0.9)) return ['allowed' => true, 'warning' => __('You are approaching your monthly appointments limit (:current/:max).', ['current' => $current, 'max' => $max])];
        return ['allowed' => true];
    }

    private function percentage(int $current, int $max): int|float
    {
        if ($max === -1) return 0;
        if ($max === 0) return 100;
        return min(100, round(($current / $max) * 100));
    }
}
