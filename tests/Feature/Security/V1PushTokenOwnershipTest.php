<?php

namespace Tests\Feature\Security;

use App\Models\PushToken;
use App\Http\Middleware\InitializeTenancyByToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('security')]
class V1PushTokenOwnershipTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(InitializeTenancyByToken::class);
    }

    #[Test]
    public function push_token_registration_ignores_client_owner_override(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/push-tokens', [
                'token' => 'customer-device-token',
                'platform' => 'android',
                'owner_type' => 'user',
                'owner_id' => $this->admin->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.owner_type', 'user')
            ->assertJsonPath('data.owner_id', $this->customer->id);

        $this->assertDatabaseHas('push_tokens', [
            'token' => 'customer-device-token',
            'owner_type' => 'user',
            'owner_id' => $this->customer->id,
        ], 'tenant');
    }

    #[Test]
    public function user_cannot_deactivate_another_users_push_token(): void
    {
        $token = PushToken::create([
            'owner_type' => 'user',
            'owner_id' => $this->admin->id,
            'platform' => 'web',
            'token' => 'admin-device-token',
            'device_name' => 'Admin browser',
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        $this->actingAs($this->customer)
            ->deleteJson('/api/v1/push-tokens/' . $token->id)
            ->assertNotFound();

        $this->assertDatabaseHas('push_tokens', [
            'id' => $token->id,
            'is_active' => true,
        ], 'tenant');
    }
}
