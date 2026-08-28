<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('feature')]
#[Group('admin')]
#[Group('onboarding')]
class OnboardingControllerTest extends TenantTestCase
{
    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('admin.onboarding'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_wizard(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 0]);

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.onboarding'));

        $response->assertOk();
        $response->assertViewIs('admin.onboarding.wizard-v2');
        $response->assertViewHas('currentStep');
        $response->assertViewHas('bookingUrl');
        $response->assertViewHas('businessName');
    }

    #[Test]
    public function already_completed_admin_is_redirected_to_dashboard(): void
    {
        Setting::updateOrCreate(['id' => 1], ['onboarding_completed' => true, 'onboarding_step' => 4]);

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.onboarding'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function step1_saves_contact_info_without_requiring_business_name(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 0]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.onboarding.step1'), [
            'phone'   => '+966500000001',
            'address' => 'Riyadh',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'next_step' => 2]);

        $this->assertDatabaseHas('settings', [
            'phone'           => '+966500000001',
            'address'         => 'Riyadh',
            'onboarding_step' => 1,
        ]);
    }

    #[Test]
    public function step1_requires_phone(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 0]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.onboarding.step1'), [
            'address' => 'Riyadh',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    #[Test]
    public function step2_saves_service_choice(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 1]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.onboarding.step2'), [
            'name'      => 'Mohamed Ali',
            'specialty' => 'Barber',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'next_step' => 3]);

        $this->assertDatabaseHas('settings', [
            'onboarding_step' => 2,
        ]);
    }

    #[Test]
    public function step3_saves_reminder_preference(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 2]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.onboarding.step3'), [
            'name'     => 'Haircut',
            'duration' => 30,
            'price'    => 50,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'next_step' => 4]);

        $this->assertDatabaseHas('settings', [
            'onboarding_step' => 3,
        ]);
    }

    #[Test]
    public function complete_marks_onboarding_done_and_redirects_to_dashboard(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 3]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.onboarding.complete'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('settings', [
            'onboarding_completed' => 1,
        ]);
    }

    #[Test]
    public function complete_sets_trial_activated_at_on_subscription(): void
    {
        Setting::first()?->update(['onboarding_completed' => false, 'onboarding_step' => 3]);

        $tenantId          = tenant('id');
        $centralConnection = config('tenancy.database.central_connection', 'sqlite');
        $db                = \Illuminate\Support\Facades\DB::connection($centralConnection);

        $planId = $db->table('subscription_plans')->insertGetId([
            'name'          => 'Trial Plan',
            'slug'          => 'trial',
            'price'         => 0,
            'billing_cycle' => 'monthly',
            'trial_days'    => 14,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $db->table('tenant_subscriptions')->insert([
            'tenant_id'            => $tenantId,
            'subscription_plan_id' => $planId,
            'status'               => 'trial',
            'amount_paid'          => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->actingAs($this->admin);

        $this->postJson(route('admin.onboarding.complete'));

        $sub = $db->table('tenant_subscriptions')->where('tenant_id', $tenantId)->first();
        $this->assertNotNull($sub?->activated_at);
    }
}
