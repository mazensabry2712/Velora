<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Subscription\SubscriptionLifecycle;
use App\Mail\WelcomeTenantMail;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

final class FinalizeTenantProvisioning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public readonly Tenant $tenant) {}

    public function handle(): void
    {
        $tenant = $this->tenant->fresh();
        $data = $this->tenantData($tenant);
        $this->markProvisioning($tenant, 'finalizing');

        try {
            $email = (string) ($data['provisioning_email'] ?? $data['email'] ?? '');
            $businessName = (string) ($data['name'] ?? $data['business_name'] ?? 'Tenant');
            $language = (string) ($data['language'] ?? 'en');

            $tenant->run(function () use ($email, $businessName, $language): void {
                $adminRole = Role::firstOrCreate([
                    'name' => 'Admin Tenant',
                    'guard_name' => 'web',
                ]);

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $businessName,
                        'password' => $this->decryptPassword(),
                    ]
                );

                $user->assignRole($adminRole);

                Setting::firstOrCreate(
                    ['id' => 1],
                    [
                        'business_name' => $businessName,
                        'language' => $language,
                        'timezone' => 'UTC',
                        'booking_enabled' => true,
                        'queue_enabled' => true,
                        'available_languages' => json_encode(config('localizer.supported_locales', ['en', 'ar'])),
                    ]
                );
            });

            $planId = isset($data['subscription_plan_id']) ? (int) $data['subscription_plan_id'] : null;
            $trialPlan = $planId
                ? SubscriptionPlan::where('is_active', true)->find($planId)
                : null;
            $trialPlan ??= SubscriptionPlan::where('is_active', true)
                ->orderBy('price', 'asc')
                ->firstOrFail();

            $trialStartsAt = now();
            $trialEndsAt = $trialStartsAt->copy()->addDays(SubscriptionLifecycle::TRIAL_DAYS);
            $lockedAt = SubscriptionLifecycle::lockedAt($trialEndsAt);
            $deletionAt = SubscriptionLifecycle::deletionAt($trialEndsAt);

            TenantSubscription::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $trialPlan->id,
                    'status' => 'trial',
                    'trial_ends_at' => $trialEndsAt,
                    'read_only_ends_at' => $lockedAt,
                    'locked_at' => $lockedAt,
                    'deletion_at' => $deletionAt,
                    'starts_at' => $trialStartsAt,
                    'ends_at' => null,
                    'amount_paid' => 0,
                    'payment_method' => $this->gateway((string) ($data['country'] ?? 'US')),
                    'notes' => 'Auto-created 7-day trial. Read-only for 14 days, locked for 6 days, then permanently deleted.',
                ]
            );

            $domainModel = $tenant->domains()->first();
            $domain = (string) ($domainModel?->domain ?? '');

            if ($domainModel && str_ends_with($domain, '.test')) {
                (new LinkTenantDomain($domainModel))->handle();
            }

            $token = (string) ($data['provisioning_token'] ?? '');

            $tenant->update([
                'provisioning_status' => 'ready',
                'provisioning_message' => 'Workspace ready.',
                'provisioning_ready_at' => now()->toIso8601String(),
                'provisioning_redirect_url' => $this->handoffUrl($domain, $token),
            ]);

            if ($email !== '') {
                Mail::to($email)->queue(new WelcomeTenantMail(
                    $businessName,
                    $tenant->id,
                    $domain,
                    SubscriptionLifecycle::TRIAL_DAYS
                ));
            }
        } catch (\Throwable $e) {
            $this->markProvisioning($tenant, 'failed', 'We could not finish setting up your workspace.');

            Log::error('Tenant provisioning finalization failed.', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function decryptPassword(): string
    {
        return decrypt((string) data_get($this->tenantData($this->tenant), 'provisioning_password'));
    }

    /** @return array<string, mixed> */
    private function tenantData(Tenant $tenant): array
    {
        $raw = $tenant->getRawOriginal('data');
        if (is_array($raw)) {
            return $raw;
        }
        return is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    }

    private function markProvisioning(Tenant $tenant, string $status, ?string $message = null): void
    {
        $tenant->update([
            'provisioning_status' => $status,
            'provisioning_message' => $message,
        ]);
    }

    private function gateway(string $country): string
    {
        $code = strtoupper(trim($country));
        if ($code === 'EG') {
            return 'paymob';
        }
        if (in_array($code, ['SA', 'AE', 'KW', 'BH', 'OM', 'QA'], true)) {
            return 'moyasar';
        }
        return 'stripe';
    }

    private function handoffUrl(string $domain, string $token): string
    {
        $scheme = str_starts_with(config('app.url', 'http://velora.test'), 'https') ? 'https' : 'http';
        return $scheme.'://'.$domain.'/__velora/provisioning/'.$token;
    }
}
