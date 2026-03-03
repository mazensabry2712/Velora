<?php

namespace Tests\Feature\Admin;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('admin')]
#[Group('settings')]
class SettingControllerTest extends TenantTestCase
{
    // ── Page view ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings'));

        $response->assertOk();
        $response->assertViewIs('admin.settings.index');
        $response->assertViewHas('settings');
    }

    #[Test]
    public function guests_are_redirected_from_settings_page(): void
    {
        $response = $this->get(route('admin.settings'));

        $response->assertRedirect(route('login'));
    }

    // ── save ──────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_save_basic_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.settings.save'), [
            'business_name' => 'My Awesome Clinic',
            'phone'         => '0501234567',
            'address'       => 'Riyadh, Saudi Arabia',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('settings', [
            'tenant_id'     => $this->tenant->id,
            'business_name' => 'My Awesome Clinic',
        ]);
    }

    #[Test]
    public function save_creates_settings_if_not_exist(): void
    {
        $this->actingAs($this->admin);

        // Ensure no settings exist yet
        \App\Models\Setting::where('tenant_id', $this->tenant->id)->delete();

        $response = $this->postJson(route('admin.api.settings.save'), [
            'business_name' => 'Brand New Clinic',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('settings', [
            'tenant_id'     => $this->tenant->id,
            'business_name' => 'Brand New Clinic',
        ]);
    }

    #[Test]
    public function save_updates_existing_settings(): void
    {
        $this->actingAs($this->admin);

        // Create initial settings
        \App\Models\Setting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            ['business_name' => 'Old Name'],
        );

        $response = $this->postJson(route('admin.api.settings.save'), [
            'business_name' => 'New Name',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('settings', [
            'tenant_id'     => $this->tenant->id,
            'business_name' => 'New Name',
        ]);
        $this->assertDatabaseMissing('settings', [
            'tenant_id'     => $this->tenant->id,
            'business_name' => 'Old Name',
        ]);
    }

    #[Test]
    public function save_rejects_invalid_logo_mime_type(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('admin.api.settings.save'), [
            'logo' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['logo']);
    }

    #[Test]
    public function save_accepts_valid_logo_image(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('admin.api.settings.save'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Logo path should be stored
        $settings = \App\Models\Setting::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($settings->logo);
    }

    #[Test]
    public function save_validates_social_url_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.settings.save'), [
            'facebook' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['facebook']);
    }

    #[Test]
    public function save_accepts_valid_social_url(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.settings.save'), [
            'facebook' => 'https://facebook.com/myclinic',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function save_returns_updated_settings_data_in_response(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.settings.save'), [
            'business_name' => 'Response Check Clinic',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'data']);
    }
}
