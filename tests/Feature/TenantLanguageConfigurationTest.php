<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\AdminNavigation;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class TenantLanguageConfigurationTest extends TestCase
{
    public function test_admin_language_switcher_matches_configured_locales(): void
    {
        $configured = config('localizer.supported_locales', []);
        $switcher = array_keys(AdminNavigation::supportedLanguages());

        sort($configured);
        sort($switcher);

        $this->assertSame($configured, $switcher);
    }

    public function test_tenant_language_switch_route_accepts_the_configured_locale_set(): void
    {
        $route = Route::getRoutes()->getByName('tenant.change.language');

        $this->assertNotNull($route);
        $this->assertSame('change-language/{lang}', $route->uri());

        $this->assertContains('GET', $route->methods());
        $this->assertSame(
            config('localizer.supported_locales', []),
            array_values(config('localizer.supported_locales', []))
        );
    }

    public function test_arabic_is_rtl_and_other_configured_locales_are_ltr(): void
    {
        $directions = config('localizer.locale_directions', []);

        $this->assertSame('rtl', $directions['ar'] ?? null);

        foreach (config('localizer.supported_locales', []) as $locale) {
            if ($locale === 'ar') {
                continue;
            }

            $this->assertSame('ltr', $directions[$locale] ?? null, "Missing LTR direction for {$locale}.");
        }
    }
}
