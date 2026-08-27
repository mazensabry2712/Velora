<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\EnsureTokenBelongsToTenant;
use App\Http\Middleware\InitializeTenancyByToken;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TenantTestCase;

class TenantTokenBindingTest extends TenantTestCase
{
    public function test_initialize_tenancy_does_not_treat_bearer_token_as_tenant_id(): void
    {
        $request = Request::create('/api/v1/profile', 'GET');
        $request->headers->set('Authorization', 'Bearer 999|not-a-tenant-id');

        $response = app(InitializeTenancyByToken::class)->handle($request, fn () => response()->noContent());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Tenant identifier is required', $response->getContent());
    }

    public function test_token_binding_accepts_token_scoped_to_current_tenant(): void
    {
        $token = new class extends PersonalAccessToken {
            public array $testAbilities = [];

            public function can($ability): bool
            {
                return in_array($ability, $this->testAbilities, true);
            }
        };

        $token->testAbilities = ['tenant:' . $this->tenant->id];

        $request = Request::create('/api/v1/profile', 'GET');
        $request->setUserResolver(fn () => new class($token) {
            public function __construct(private PersonalAccessToken $token) {}

            public function currentAccessToken(): PersonalAccessToken
            {
                return $this->token;
            }
        });

        $response = app(EnsureTokenBelongsToTenant::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_token_binding_rejects_token_scoped_to_another_tenant(): void
    {
        $token = new class extends PersonalAccessToken {
            public array $testAbilities = [];

            public function can($ability): bool
            {
                return in_array($ability, $this->testAbilities, true);
            }
        };

        $token->testAbilities = ['tenant:another-tenant'];

        $request = Request::create('/api/v1/profile', 'GET');
        $request->setUserResolver(fn () => new class($token) {
            public function __construct(private PersonalAccessToken $token) {}

            public function currentAccessToken(): PersonalAccessToken
            {
                return $this->token;
            }
        });

        $response = app(EnsureTokenBelongsToTenant::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Tenant mismatch', $response->getContent());
    }
}
