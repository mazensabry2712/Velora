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

    private function postSignup(array $overrides = []): TestResponse
    {
        $payload = $this->data($overrides);

        return $this->withServerVariables([
            'HTTP_HOST' => (string) env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => (string) env('APP_DOMAIN', 'velora.test'),
        ])->post('/signup', $payload);
    }

    private function assertValidationError(string $field, mixed $value): void
    {
        $response = $this->postSignup([$field => $value]);

        $response->assertStatus(422)->assertJsonValidationErrors([$field]);
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
        $response = $this->postSignup(['business_name' => 'AB']);
        $this->assertLessThan(422, $response->status());
    }

    public function test_business_name_rejects_more_than_100_characters(): void
    {
        $this->assertValidationError('business_name', str_repeat('A', 101));
    }

    public function test_business_type_is_optional(): void
    {
        $response = $this->postSignup(['business_type' => null]);
        $this->assertLessThan(422, $response->status());
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
            $response = $this->postSignup(['email' => $email]);
            $response->assertStatus(422)->assertJsonValidationErrors(['email']);
        }
    }

    public function test_email_accepts_a_valid_address(): void
    {
        $response = $this->postSignup(['email' => 'valid-signup@gmail.com']);
        $this->assertLessThan(422, $response->status());
    }

    public function test_email_is_not_accepted_twice_case_insensitively(): void
    {
        $email = 'duplicate-signup@gmail.com';

        $first = $this->postSignup(['email' => $email]);
        $first->assertRedirect();

        $second = $this->postSignup(['email' => strtoupper($email)]);
        $second->assertStatus(422)->assertJsonValidationErrors(['email']);
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
        $response = $this->postSignup(['password_confirmation' => 'different-password']);
        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_country_is_optional(): void
    {
        $response = $this->postSignup(['country' => null]);
        $this->assertLessThan(422, $response->status());
    }

    public function test_country_must_be_exactly_two_characters_when_present(): void
    {
        foreach (['U', 'USA', '123', ''] as $country) {
            $response = $this->postSignup(['country' => $country]);
            $response->assertStatus(422)->assertJsonValidationErrors(['country']);
        }
    }

    public function test_language_must_be_supported(): void
    {
        $this->assertValidationError('language', 'xx');
    }

    public function test_supported_language_is_accepted(): void
    {
        $response = $this->postSignup(['language' => 'ar']);
        $this->assertLessThan(422, $response->status());
    }

    public function test_terms_are_required_and_must_be_accepted(): void
    {
        foreach ([null, '0', false, 'off'] as $terms) {
            $response = $this->postSignup(['terms' => $terms]);
            $response->assertStatus(422)->assertJsonValidationErrors(['terms']);
        }
    }

    public function test_terms_accept_one(): void
    {
        $response = $this->postSignup(['terms' => '1']);
        $this->assertLessThan(422, $response->status());
    }

    public function test_plan_id_is_optional(): void
    {
        $response = $this->postSignup(['plan_id' => null]);
        $this->assertLessThan(422, $response->status());
    }

    public function test_plan_id_must_be_an_integer_when_present(): void
    {
        $this->assertValidationError('plan_id', 'not-an-integer');
    }

    public function test_plan_id_must_exist_when_present(): void
    {
        $this->assertValidationError('plan_id', 999999999);
    }

    public function test_unknown_fields_are_not_used_for_mass_assignment(): void
    {
        $payload = $this->data([
            'is_super_admin' => true,
            'role' => 'Super Admin',
            'owner_type' => 'super-admin',
        ]);

        $response = $this->postSignup($payload);
        $response->assertRedirect();

        $tenant = Tenant::where('id', $payload['subdomain'])->firstOrFail();
        $this->assertFalse((bool) ($tenant->getAttribute('is_super_admin') ?? false));
        $this->assertFalse((bool) ($tenant->getAttribute('role') ?? false));
        $this->assertSame($tenant->id, $payload['subdomain']);
    }

    public function test_valid_signup_creates_a_tenant(): void
    {
        $payload = $this->data();
        $response = $this->postSignup($payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['id' => $payload['subdomain']]);
    }
}
