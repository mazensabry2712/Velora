<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SystemSetting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SignupUiContractTest extends TestCase
{
    #[Test]
    public function signup_page_exposes_the_complete_registration_form_contract(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/signup');

        $response->assertOk()
            ->assertSee('<form id="signupForm" method="POST"', false)
            ->assertSee('name="business_name"', false)
            ->assertSee('name="business_type"', false)
            ->assertSee('name="subdomain"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="country"', false)
            ->assertSee('name="language"', false)
            ->assertSee('name="terms"', false)
            ->assertSee('name="_token"', false);
    }

    #[Test]
    public function signup_page_is_rendered_with_the_configured_direction_for_every_supported_locale(): void
    {
        $locales = array_values(array_unique(config('localizer.supported_locales', [])));
        $directions = config('localizer.locale_directions', []);
        $configuredDefault = config('localizer.omitted_locale', 'ar');
        $publicDefault = SystemSetting::get('public_default_locale', $configuredDefault);
        $default = is_string($publicDefault) && in_array($publicDefault, $locales, true)
            ? $publicDefault
            : $configuredDefault;

        self::assertNotEmpty($locales);

        foreach ($locales as $locale) {
            $path = $locale === $default ? '/signup' : "/{$locale}/signup";

            $response = $this->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])->withSession([])->get($path);

            $response->assertOk()
                ->assertSee('lang="' . $locale . '"', false)
                ->assertSee('dir="' . ($directions[$locale] ?? 'ltr') . '"', false)
                ->assertSee('name="language"', false);
        }
    }

    #[Test]
    public function signup_page_preserves_old_values_without_rendering_sensitive_password_values(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->from('/signup')->post('/signup', [
            'business_name' => 'UI Contract Business',
            'business_type' => 'Clinic',
            'subdomain' => 'invalid_',
            'email' => 'ui-contract-invalid@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Different123!',
            'country' => 'US',
            'language' => 'fr',
        ]);

        $response->assertRedirect('/signup');

        $followUp = $this->get('/signup');
        $followUp->assertOk();

        self::assertStringNotContainsString('Password123!', $followUp->getContent());
        self::assertStringNotContainsString('Different123!', $followUp->getContent());
    }

    private function centralHost(): string
    {
        return (string) env('APP_DOMAIN', 'velora.test');
    }
}
