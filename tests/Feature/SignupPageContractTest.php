<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\CheckMaintenanceMode;
use Tests\TestCase;

class SignupPageContractTest extends TestCase
{
    private function centralHost(): string
    {
        return env('APP_DOMAIN', 'velora.test');
    }

    private function centralRequest(): static
    {
        return $this
            ->withoutMiddleware(CheckMaintenanceMode::class)
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ]);
    }

    public function test_arabic_signup_page_resolves_on_the_central_domain(): void
    {
        $response = $this->centralRequest()->get('/ar/signup');

        $response->assertOk();
        $response->assertSee('id="signupForm"', false);
        $response->assertSee('name="subdomain"', false);
        $response->assertDontSee('name="coupon"', false);
        $response->assertDontSee('name="promo_code"', false);
    }

    public function test_signup_markup_uses_backend_field_names_and_has_no_promo_code_ui(): void
    {
        $markup = file_get_contents(resource_path('views/landing/signup.blade.php'));

        $this->assertIsString($markup);
        $this->assertStringContainsString('method="POST"', $markup);
        $this->assertStringContainsString("@csrf", $markup);
        $this->assertStringContainsString('name="business_name"', $markup);
        $this->assertStringContainsString('name="subdomain"', $markup);
        $this->assertStringContainsString('name="email"', $markup);
        $this->assertStringContainsString('name="password"', $markup);
        $this->assertStringContainsString('name="password_confirmation"', $markup);
        $this->assertStringContainsString('name="country"', $markup);
        $this->assertStringContainsString('name="language"', $markup);
        $this->assertStringContainsString('name="terms"', $markup);
        $this->assertStringContainsString('togglePassword', $markup);

        $this->assertStringNotContainsString('name="coupon"', $markup);
        $this->assertStringNotContainsString('name="promo_code"', $markup);
        $this->assertStringNotContainsString('toggleCoupon', $markup);
        $this->assertStringNotContainsString('vs-coupon', $markup);
    }

    public function test_signup_backend_does_not_accept_promo_code_as_part_of_registration_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Auth/TenantRegistrationController.php'));
        $service = file_get_contents(app_path('Services/TenantRegistrationService.php'));

        $this->assertIsString($controller);
        $this->assertIsString($service);

        $this->assertStringNotContainsString("'promo_code'", $controller);
        $this->assertStringNotContainsString('PromoCode', $service);
        $this->assertStringNotContainsString('promo_code', $service);
        $this->assertStringNotContainsString('promo:', $service);
    }

    public function test_signup_post_rejects_missing_required_fields_before_registration(): void
    {
        $response = $this->centralRequest()->post('/signup', []);

        $response->assertSessionHasErrors([
            'business_name',
            'subdomain',
            'email',
            'password',
            'terms',
        ]);
    }

    public function test_signup_post_rejects_invalid_subdomain_before_registration(): void
    {
        $response = $this->centralRequest()->post('/signup', [
            'business_name' => 'Test Business',
            'subdomain' => 'Invalid Subdomain',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
            'country' => 'US',
            'language' => 'en',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_signup_post_rejects_mismatched_passwords_before_registration(): void
    {
        $response = $this->centralRequest()->post('/signup', [
            'business_name' => 'Test Business',
            'subdomain' => 'test-business',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
            'terms' => '1',
            'country' => 'US',
            'language' => 'en',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
