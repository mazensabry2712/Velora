<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Http\Middleware\EnsureTokenBelongsToTenant;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('security')]
final class TenantIsolationSecurityScenarioTest extends TenantTestCase
{
    #[Test]
    public function token_scoped_to_current_tenant_is_accepted(): void
    {
        $token = $this->admin->createToken('qa-tenant-a', [
            'tenant:' . $this->tenant->id,
        ])->accessToken;

        $request = Request::create('/api/v1/appointments', 'GET');
        $request->setUserResolver(fn () => $this->admin);
        $this->admin->withAccessToken($this->admin->tokens()->where('name', 'qa-tenant-a')->latest('id')->first());

        $response = app(EnsureTokenBelongsToTenant::class)->handle(
            $request,
            fn (Request $request) => response()->json(['passed' => true]),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['passed' => true], $response->getData(true));
        $this->assertNotSame('', $token);
    }

    #[Test]
    public function token_from_tenant_a_is_rejected_when_tenant_b_is_initialized(): void
    {
        $tenantAToken = $this->admin->createToken('qa-tenant-a-isolation', [
            'tenant:' . $this->tenant->id,
        ]);

        $tenantB = Tenant::create([
            'id' => 'qa-isolation-' . uniqid(),
            'name' => 'QA Isolation Tenant B',
        ]);

        try {
            tenancy()->initialize($tenantB);

            $request = Request::create('/api/v1/appointments', 'GET');
            $request->setUserResolver(fn () => $this->admin);
            $this->admin->withAccessToken($tenantAToken->token);

            $response = app(EnsureTokenBelongsToTenant::class)->handle(
                $request,
                Closure::fromCallable(static fn (Request $request) => response()->json(['passed' => true])),
            );

            $this->assertSame(403, $response->getStatusCode());
            $this->assertSame('Tenant mismatch', $response->getData(true)['error']);
            $this->assertSame(
                'The access token is not authorized for this tenant.',
                $response->getData(true)['message'],
            );
        } finally {
            tenancy()->end();
        }
    }
}
