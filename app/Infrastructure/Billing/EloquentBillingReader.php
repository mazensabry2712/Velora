<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Billing\Contracts\BillingReader;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

final class EloquentBillingReader implements BillingReader
{
    public function expiredOverview(string|int $tenantId): array
    {
        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('tenant_subscriptions.tenant_id', $tenantId)
            ->orderByDesc('tenant_subscriptions.created_at')
            ->select(
                'tenant_subscriptions.*',
                'subscription_plans.name as plan_name',
                'subscription_plans.price',
                'subscription_plans.billing_cycle',
            )
            ->first();

        return [
            'subscription' => $subscription,
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('price', 'asc')->get(),
            'invoices' => DB::connection('mysql')
                ->table('tenant_subscriptions')
                ->where('tenant_id', $tenantId)
                ->where('amount_paid', '>', 0)
                ->orderByDesc('created_at')
                ->get(),
        ];
    }

    public function stripeCustomerId(string|int $tenantId): ?string
    {
        return DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('stripe_customer_id')
            ->orderByDesc('created_at')
            ->value('stripe_customer_id');
    }
}
