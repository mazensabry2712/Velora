<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\SuperAdminTestCase;

#[Group('feature')]
#[Group('security')]
#[Group('authentication')]
class SuperAdminWebLoginRateLimitTest extends SuperAdminTestCase
{
    #[Test]
    public function invalid_super_admin_web_login_attempts_are_rate_limited(): void
    {
        $email = $this->superAdmin->email;
        $key = 'super-admin-web-login:' . request()->ip() . ':' . strtolower($email);

        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('super-admin.login.post'), [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertRedirect()
              ->assertSessionHasErrors('email');
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));

        $this->post(route('super-admin.login.post'), [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertRedirect()
          ->assertSessionHasErrors('email');
    }

    #[Test]
    public function successful_super_admin_web_login_clears_rate_limiter(): void
    {
        $email = $this->superAdmin->email;
        $key = 'super-admin-web-login:' . request()->ip() . ':' . strtolower($email);

        RateLimiter::hit($key, 60);

        $this->post(route('super-admin.login.post'), [
            'email' => $email,
            'password' => 'password',
        ])->assertRedirect(route('super-admin.dashboard'));

        $this->assertFalse(RateLimiter::tooManyAttempts($key, 5));
    }
}
