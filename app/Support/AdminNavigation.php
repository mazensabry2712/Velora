<?php

namespace App\Support;

class AdminNavigation
{
    /**
     * Primary nav links shown in the admin navbar (desktop + mobile).
     *
     * @return array<int, array{url: string, match: string, label: string, icon: string}>
     */
    public static function items(): array
    {
        $items = [
            [
                'url' => '/admin/appointments',
                'match' => 'admin/appointments*',
                'label' => __('Appointments'),
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
            [
                'url' => '/admin/queue',
                'match' => 'admin/queue*',
                'label' => __('Queue'),
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0',
            ],
            [
                'url' => '/admin/staff',
                'match' => 'admin/staff*',
                'label' => __('Staff'),
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            ],
            [
                'url' => '/admin/customers',
                'match' => 'admin/customers*',
                'label' => __('Customers'),
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            ],
            [
                'url' => '/admin/reports',
                'match' => 'admin/reports*',
                'label' => __('Reports'),
                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            [
                'url' => '/admin/settings',
                'match' => 'admin/settings*',
                'label' => __('Settings'),
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ],
        ];

        if (auth()->check() && auth()->user()->isAdminTenant()) {
            $items[] = [
                'url' => route('admin.subscription.index'),
                'match' => 'admin/subscription*',
                'label' => __('Subscription'),
                'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            ];
            $items[] = [
                'url' => '/admin/assistants',
                'match' => 'admin/assistants*',
                'label' => __('Assistants'),
                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.636-6.364l.707.707M6 20l6-6 6 6',
            ];
        }

        return $items;
    }

    /**
     * Languages selectable from the navbar language switcher.
     *
     * @return array<string, string> language code => display label
     */
    public static function supportedLanguages(): array
    {
        return [
            'en' => 'EN', 'ar' => 'عربي', 'de' => 'DE', 'es' => 'ES', 'fr' => 'FR',
            'hi' => 'HI', 'id' => 'ID', 'it' => 'IT', 'ja' => 'JA', 'ko' => 'KO',
            'nl' => 'NL', 'pt' => 'PT', 'ru' => 'RU', 'tr' => 'TR', 'zh' => '中文',
        ];
    }
}
