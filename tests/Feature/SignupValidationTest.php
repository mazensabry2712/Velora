<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class SignupValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Validation Test Clinic',
            'business_type' => 'Clinic',
            'subdomain' => 'valid-' . substr(md5(uniqid('', true)), 0, 8),
            'email' => 'signup-' . substr(md5(uniqid('', true)), 0, 8) . '@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'US',
            'language' => 'en',
            'terms' => '1',
        ], $overrides);
    }

    private function createActivePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Signup Validation Plan',
            'slug' => 'signup-validation-' . substr(md5(uniqid('', true)), 0, 12),
            'description' => 'Plan used by signup validation tests',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'max_users' => null,
            'max_appointments' => null,
            'storage_limit' => null,
            'features' => [],
            'is_active' => true,
            'is_popular' => false,
            'trial_days' => 7,
        ]);
    }

    private function postSignup(array $overrides = []): TestResponse
    {
        $payload = $this->data($overrides);
        $plan = $this->createActivePlan();

        return $this->withServerVariables([
            'HTTP_HOST' => (string) env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => (string) env('APP_DOMAIN', 'velora.test'),
        ])->post('/signup', $payload + ['plan_id' => $plan->id]);
    }

    private function assertValidationError(string $field, mixed $value): void
    {
        $response = $this->postSignup([$field => $value]);

        $response->assertRedirect();
        $this->assertTrue(
            $response->getSession()->has('errors'),
            'Expected validation errors in the session. Response: ' . $response->getContent()
        );
        $this->assertTrue(
            $response->getSession()->get('errors')->has($field),
            "Expected validation error for [{$field}]."
        );
    }

    private function assertAccepted(array $overrides = []): void
    {
        $response = $this->postSignup($overrides);
        $this->assertLessThan(400, $response->status(), $response->getContent());
    }

    public function test_signup_page_is_gettable(): void
    {
        $this->get('/signup')->assertSuccessful();
    }

    public function test_business_name_is_required(): void
    {
        $this->assertValidationError('business_name', '');
    }

    public function test_business_name_requires_at_least_two_characters(): void
    {
        $this->assertValidationError('business_name', 'A');
    }

    public function test_business_name_accepts_two_characters(): void
    {
        $this->assertAccepted(['business_name' => 'AB']);
    }

    public function test_business_name_rejects_more_than_100_characters(): void
    {
        $this->assertValidationError('business_name', str_repeat('A', 101));
    }

    public function test_business_type_is_optional(): void
    {
        $this->assertAccepted(['business_type' => null]);
    }

    public function test_business_type_rejects_more_than_60_characters(): void
    {
        $this->assertValidationError('business_type', str_repeat('B', 61));
    }

    public function test_subdomain_is_required(): void
    {
        $this->assertValidationError('subdomain', '');
    }

    public function test_subdomain_minimum_length_is_three(): void
    {
        $this->assertValidationError('subdomain', 'ab');
    }

    public function test_subdomain_maximum_length_is_32(): void
    {
        $this->assertValidationError('subdomain', str_repeat('a', 33));
    }

    public function test_subdomain_rejects_uppercase_and_is_not_silently_accepted(): void
    {
        $this->assertValidationError('subdomain', 'ABC');
    }

    public function test_subdomain_rejects_underscore(): void
    {
        $this->assertValidationError('subdomain', 'my_store');
    }

    public function test_subdomain_rejects_leading_hyphen(): void
    {
        $this->assertValidationError('subdomain', '-abc');
    }

    public function test_subdomain_rejects_trailing_hyphen(): void
    {
        $this->assertValidationError('subdomain', 'abc-');
    }

    public function test_email_is_required(): void
    {
        $this->assertValidationError('email', '');
    }

    public function test_email_rejects_malformed_values(): void
    {
        foreach (['abc', 'abc@', 'abc@invalid', 'not-an-email'] as $email) {
            $this->assertValidationError('email', $email);
        }
    }

    public function test_email_accepts_a_valid_address(): void
    {
        $this->assertAccepted(['email' => 'valid-signup@gmail.com']);
    }

    public function test_email_is_not_accepted_twice_case_insensitively(): void
    {
        $email = 'duplicate-signup-' . substr(md5(uniqid('', true)), 0, 8) . '@gmail.com';
        $subdomain = 'dup-' . substr(md5(uniqid('', true)), 0, 8);

        $first = $this->postSignup([
            'email' => $email,
            'subdomain' => $subdomain,
        ]);
        $first->assertRedirect();
        $this->assertDatabaseHas('tenants', ['id' => $subdomain]);

        $second = $this->postSignup([
            'email' => strtoupper($email),
            'subdomain' => 'dup2-' . substr(md5(uniqid('', true)), 0, 8),
        ]);

        $second->assertRedirect();
        $errors = $second->getSession()->get('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('email'));
    }

    public function test_password_is_required(): void
    {
        $this->assertValidationError('password', '');
    }

    public function test_password_requires_at_least_eight_characters(): void
    {
        $this->assertValidationError('password', '1234567');
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->assertValidationError('password_confirmation', 'different-password');
    }

    public function test_country_is_optional(): void
    {
        $this->assertAccepted(['country' => null]);
    }

    public function test_country_must_be_exactly_two_characters_when_present(): void
    {
        foreach (['U', 'USA', '123'] as $country) {
            $this->assertValidationError('country', $country);
        }
    }

    public function test_language_must_be_supported(): void
    {
        $this->assertValidationError('language', 'xx');
    }

    public function test_supported_language_is_accepted(): void
    {
        $this->assertAccepted(['language' => 'ar']);
    }

    public function test_terms_are_required_and_must_be_accepted(): void
    {
        foreach ([null, '0', false, 'off'] as $terms) {
            $this->assertValidationError('terms', $terms);
        }
    }

    public function test_terms_accept_one(): void
    {
        $this->assertAccepted(['terms' => '1']);
    }

    public function test_plan_id_is_optional(): void
    {
        $response = $this->postSignup();
        $response->assertRedirect();
    }

    public function test_plan_id_must_be_an_integer_when_present(): void
    {
        $response = $this->postSignup(['plan_id' => 'not-an-integer']);
        $response->assertRedirect();
        $this->assertTrue($response->getSession()->get('errors')->has('plan_id'));
    }

    public function test_plan_id_must_exist_when_present(): void
    {
        $response = $this->postSignup(['plan_id' => 999999999]);
        $response->assertRedirect();
        $this->assertTrue($response->getSession()->get('errors')->has('plan_id'));
    }

    public function test_unknown_fields_are_not_used_for_mass_assignment(): void
    {
        $payload = $this->data([
            'is_super_admin' => true,
            'role' => 'Super Admin',
            'owner_type' => 'super-admin',
        ]);
        $subdomain = $payload['subdomain'];
        $plan = $this->createActivePlan();

        $response = $this->withServerVariables([
            'HTTP_HOST' => (string) env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => (string) env('APP_DOMAIN', 'velora.test'),
        ])->post('/signup', $payload + ['plan_id' => $plan->id]);

        $response->assertRedirect();

        $tenant = Tenant::whereKey($subdomain)->firstOrFail();
        $this->assertSame($subdomain, $tenant->getKey());
        $this->assertFalse((bool) ($tenant->getAttribute('is_super_admin') ?? false));
        $this->assertFalse((bool) ($tenant->getAttribute('role') ?? false));
    }

    public function test_valid_signup_creates_a_tenant(): void
    {
        $payload = $this->data();
        $plan = $this->createActivePlan();

        $response = $this->withServerVariables([
            'HTTP_HOST' => (string) env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => (string) env('APP_DOMAIN', 'velora.test'),
        ])->post('/signup', $payload + ['plan_id' => $plan->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['id' => $payload['subdomain']]);
    }
}
