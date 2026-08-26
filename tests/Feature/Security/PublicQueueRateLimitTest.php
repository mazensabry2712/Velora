<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class PublicQueueRateLimitTest extends TenantTestCase
{
    #[Test]
    public function public_queue_status_lookup_is_rate_limited_per_tenant_and_ip(): void
    {
        $key = 'public-queue-status:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip();
        RateLimiter::clear($key);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/queue/status/UNKNOWN-' . $i)
                ->assertStatus(404)
                ->assertJsonPath('success', false);
        }

        $this->getJson('/api/queue/status/UNKNOWN-final')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Too many requests. Please try again later.');

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 60));
    }
}
