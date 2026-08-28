<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Auth\TenantProvisioningController;
use App\Mail\VerifyTenantEmailMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class TenantEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_route_is_registered_on_central_domain(): void
    {
        $route = Route::getRoutes()->getByName('tenant.email.verify');

        $this->assertNotNull($route);
        $this->assertSame('email/verify/{token}', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertSame([TenantProvisioningController::class, 'verifyEmail'], $route->getAction()['uses']);
        $this->assertNotContains(\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class, $route->middleware());
    }

    public function test_resend_verification_route_is_registered_on_central_signup_flow(): void
    {
        $route = Route::getRoutes()->getByName('signup.provisioning.resend');

        $this->assertNotNull($route);
        $this->assertSame('signup/provisioning/{token}/resend-verification', $route->uri());
        $this->assertContains('POST', $route->methods());
    }

    public function test_verification_mail_contains_the_verification_url(): void
    {
        $mail = new VerifyTenantEmailMail(
            'Demo Business',
            'demo-business',
            'demo-business.velora.test',
            'http://velora.test/email/verify/demo-business.secret-token',
            24,
        );

        $content = $mail->content();

        $this->assertSame('emails.tenant-verify-email', $content->view);
        $this->assertSame('Demo Business', $mail->businessName);
        $this->assertSame('demo-business', $mail->tenantId);
        $this->assertSame(24, $mail->expiresInHours);
    }

    public function test_tenant_table_contains_expected_provisioning_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_status'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_token_hash'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_token_used_at'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_email'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_redirect_url'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_ready_at'));
        $this->assertTrue(Schema::hasColumn('tenants', 'provisioning_message'));
    }
}
