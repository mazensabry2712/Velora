<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tell Laravel to load translation files from /lang (not /resources/lang)
        $this->app->useLangPath(base_path('lang'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Appointment::observe(AppointmentObserver::class);

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
