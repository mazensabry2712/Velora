<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\SystemSetting;
use App\Observers\AppointmentObserver;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tell Laravel to load translation files from /lang (not /resources/lang)
        $this->app->useLangPath(base_path('lang'));

        // Register PaymentGatewayManager as a singleton so driver instances are
        // resolved from the container (supports constructor injection in gateways).
        $this->app->singleton(PaymentGatewayManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Appointment::observe(AppointmentObserver::class);

        // Share platform settings with all landing views (layout + pages)
        View::composer('layouts.landing', function ($view) {
            try {
                $appName             = SystemSetting::get('app_name', config('app.name', 'Velora'));
                $appLogoUrl          = SystemSetting::get('app_logo_url', '');
                $registrationEnabled = SystemSetting::get('registration_enabled', true);
                $defaultTrialDays    = SystemSetting::get('default_trial_days', 14);
            } catch (\Throwable $e) {
                $appName             = config('app.name', 'Velora');
                $appLogoUrl          = '';
                $registrationEnabled = true;
                $defaultTrialDays    = 14;
            }

            $view->with(compact('appName', 'appLogoUrl', 'registrationEnabled', 'defaultTrialDays'));
        });

        // Share system notifications from super-admin with all tenant admin views.
        // Only runs in tenant context (tenant() returns non-null).
        View::composer('layouts.admin', function ($view) {
            try {
                $tenantId = tenant('id');
                if (!$tenantId) {
                    return;
                }

                $notifications = DB::connection('mysql')
                    ->table('system_notifications')
                    ->where('is_sent', true)
                    ->where(function ($q) use ($tenantId) {
                        $q->where('target', 'all')
                          ->orWhere(function ($q2) use ($tenantId) {
                              $q2->where('target', 'specific')
                                 ->whereJsonContains('tenant_ids', $tenantId);
                          });
                    })
                    ->where('sent_at', '>=', now()->subDays(7))
                    ->orderByDesc('sent_at')
                    ->limit(5)
                    ->get(['id', 'title', 'message', 'type', 'sent_at']);

                $view->with('systemNotifications', $notifications);
            } catch (\Throwable $e) {
                $view->with('systemNotifications', collect());
            }
        });
    }
}
