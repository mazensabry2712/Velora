<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\SystemSetting;
use App\Observers\AppointmentObserver;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
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

        // The landing language files already contain the navigation/content keys,
        // but some deployments do not define meta_title/meta_description. Register
        // those two missing landing keys using Laravel's supported dotted-key API.
        // This keeps __('landing.meta_title') working without modifying every locale file.
        $landingMeta = [
            'ar' => [
                'landing.meta_title' => 'Velora — حجز المواعيد وإدارة الطوابير',
                'landing.meta_description' => 'منصة ذكية لإدارة المواعيد والطوابير للشركات الصغيرة.',
            ],
            'en' => [
                'landing.meta_title' => 'Velora — Smart Booking & Queue Management',
                'landing.meta_description' => 'Smart appointment booking and queue management for small businesses.',
            ],
            'fr' => [
                'landing.meta_title' => 'Velora — Gestion intelligente des rendez-vous et des files',
                'landing.meta_description' => 'Gestion intelligente des rendez-vous et des files d’attente pour les petites entreprises.',
            ],
            'es' => [
                'landing.meta_title' => 'Velora — Gestión inteligente de citas y colas',
                'landing.meta_description' => 'Gestión inteligente de citas y colas para pequeñas empresas.',
            ],
            'de' => [
                'landing.meta_title' => 'Velora — Intelligente Termin- und Warteschlangenverwaltung',
                'landing.meta_description' => 'Intelligente Termin- und Warteschlangenverwaltung für kleine Unternehmen.',
            ],
            'it' => [
                'landing.meta_title' => 'Velora — Gestione intelligente di appuntamenti e code',
                'landing.meta_description' => 'Gestione intelligente di appuntamenti e code per piccole imprese.',
            ],
            'pt' => [
                'landing.meta_title' => 'Velora — Gestão inteligente de agendamentos e filas',
                'landing.meta_description' => 'Gestão inteligente de agendamentos e filas para pequenas empresas.',
            ],
            'ru' => [
                'landing.meta_title' => 'Velora — Умное управление записями и очередями',
                'landing.meta_description' => 'Умное управление записями и очередями для малого бизнеса.',
            ],
            'zh' => [
                'landing.meta_title' => 'Velora — 智能预约与排队管理',
                'landing.meta_description' => '为小型企业提供智能预约和排队管理。',
            ],
            'ja' => [
                'landing.meta_title' => 'Velora — スマート予約・待ち行列管理',
                'landing.meta_description' => '小規模ビジネス向けのスマートな予約・待ち行列管理です。',
            ],
            'tr' => [
                'landing.meta_title' => 'Velora — Akıllı Randevu ve Kuyruk Yönetimi',
                'landing.meta_description' => 'Küçük işletmeler için akıllı randevu ve kuyruk yönetimi.',
            ],
            'hi' => [
                'landing.meta_title' => 'Velora — स्मार्ट बुकिंग और कतार प्रबंधन',
                'landing.meta_description' => 'छोटे व्यवसायों के लिए स्मार्ट अपॉइंटमेंट बुकिंग और कतार प्रबंधन।',
            ],
            'ko' => [
                'landing.meta_title' => 'Velora — 스마트 예약 및 대기열 관리',
                'landing.meta_description' => '소규모 비즈니스를 위한 스마트 예약 및 대기열 관리.',
            ],
            'nl' => [
                'landing.meta_title' => 'Velora — Slim afspraken- en wachtrijbeheer',
                'landing.meta_description' => 'Slim afspraken- en wachtrijbeheer voor kleine bedrijven.',
            ],
            'id' => [
                'landing.meta_title' => 'Velora — Manajemen Booking & Antrean Cerdas',
                'landing.meta_description' => 'Manajemen janji temu dan antrean cerdas untuk bisnis kecil.',
            ],
        ];

        foreach ($landingMeta as $locale => $lines) {
            Lang::addLines($lines, $locale);
        }

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
