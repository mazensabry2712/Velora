<?php

declare(strict_types=1);

namespace App\Infrastructure\Landing;

use App\Domain\Landing\Contracts\LandingSettingsReader;
use App\Models\SystemSetting;

final class LegacyLandingSettingsReader implements LandingSettingsReader
{
    public function read(): array
    {
        try {
            return [
                'appName' => (string) SystemSetting::get('app_name', config('app.name', 'Velora')),
                'appLogoUrl' => (string) SystemSetting::get('app_logo_url', ''),
                'registrationEnabled' => (bool) SystemSetting::get('registration_enabled', true),
                'defaultTrialDays' => (int) SystemSetting::get('default_trial_days', 14),
            ];
        } catch (\Throwable) {
            return [
                'appName' => (string) config('app.name', 'Velora'),
                'appLogoUrl' => '',
                'registrationEnabled' => true,
                'defaultTrialDays' => 14,
            ];
        }
    }
}
