<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\SuperAdminTestCase;

#[Group('feature')]
#[Group('super-admin')]
#[Group('promo-codes')]
class PromoCodeTest extends SuperAdminTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // Unauthenticated access
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('super-admin.promo-codes.index'));

        $response->assertRedirect(route('super-admin.login'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // Index
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_view_promo_codes_index(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->get(route('super-admin.promo-codes.index'));

        $response->assertOk();
    }

    #[Test]
    public function index_lists_existing_promo_codes(): void
    {
        DB::commit(); // commit so factory-created records survive the query
        PromoCode::create([
            'code'           => 'LISTME20',
            'discount_type'  => 'percent',
            'discount_value' => 20,
            'is_active'      => true,
        ]);
        DB::beginTransaction();

        $response = $this
            ->actingAs($this->superAdmin)
            ->get(route('super-admin.promo-codes.index'));

        $response->assertOk();
        $response->assertSee('LISTME20');
    }

    // ════════════════════════════════════════════════════════════════════════
    // Store
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_create_a_promo_code(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'code'           => 'WELCOME50',
                'discount_type'  => 'percent',
                'discount_value' => 50,
                'is_active'      => true,
            ]);

        $response->assertRedirect(route('super-admin.promo-codes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('promo_codes', ['code' => 'WELCOME50']);
    }

    #[Test]
    public function promo_code_is_uppercased_on_store(): void
    {
        $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'code'           => 'lowercase10',
                'discount_type'  => 'fixed',
                'discount_value' => 10,
            ]);

        $this->assertDatabaseHas('promo_codes', ['code' => 'LOWERCASE10']);
    }

    #[Test]
    public function store_requires_code(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'discount_type'  => 'percent',
                'discount_value' => 10,
            ]);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function store_requires_valid_discount_type(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'code'           => 'BADTYPE',
                'discount_type'  => 'invalid',
                'discount_value' => 10,
            ]);

        $response->assertSessionHasErrors('discount_type');
    }

    #[Test]
    public function store_rejects_duplicate_code(): void
    {
        PromoCode::create([
            'code'           => 'DUPLICATE',
            'discount_type'  => 'percent',
            'discount_value' => 10,
        ]);

        $response = $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'code'           => 'DUPLICATE',
                'discount_type'  => 'percent',
                'discount_value' => 20,
            ]);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function store_rejects_past_expiry_date(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->post(route('super-admin.promo-codes.store'), [
                'code'           => 'EXPIRED',
                'discount_type'  => 'percent',
                'discount_value' => 10,
                'expires_at'     => '2000-01-01',
            ]);

        $response->assertSessionHasErrors('expires_at');
    }

    // ════════════════════════════════════════════════════════════════════════
    // Toggle
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_toggle_promo_code_active_status(): void
    {
        $code = PromoCode::create([
            'code'           => 'TOGGLEME',
            'discount_type'  => 'percent',
            'discount_value' => 15,
            'is_active'      => true,
        ]);

        $this
            ->actingAs($this->superAdmin)
            ->patch(route('super-admin.promo-codes.toggle', $code->id));

        $this->assertFalse($code->fresh()->is_active);
    }

    #[Test]
    public function toggle_activates_an_inactive_code(): void
    {
        $code = PromoCode::create([
            'code'           => 'REACTIVATE',
            'discount_type'  => 'fixed',
            'discount_value' => 5,
            'is_active'      => false,
        ]);

        $this
            ->actingAs($this->superAdmin)
            ->patch(route('super-admin.promo-codes.toggle', $code->id));

        $this->assertTrue($code->fresh()->is_active);
    }

    #[Test]
    public function toggle_redirects_with_success_message(): void
    {
        $code = PromoCode::create([
            'code'           => 'FLASH',
            'discount_type'  => 'percent',
            'discount_value' => 5,
            'is_active'      => true,
        ]);

        $response = $this
            ->actingAs($this->superAdmin)
            ->patch(route('super-admin.promo-codes.toggle', $code->id));

        $response->assertRedirect(route('super-admin.promo-codes.index'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function toggle_returns_404_for_nonexistent_code(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->patch(route('super-admin.promo-codes.toggle', 99999));

        $response->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Destroy
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_delete_a_promo_code(): void
    {
        $code = PromoCode::create([
            'code'           => 'DELETEME',
            'discount_type'  => 'percent',
            'discount_value' => 10,
        ]);

        $response = $this
            ->actingAs($this->superAdmin)
            ->delete(route('super-admin.promo-codes.destroy', $code->id));

        $response->assertRedirect(route('super-admin.promo-codes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('promo_codes', ['id' => $code->id]);
    }

    #[Test]
    public function destroy_returns_404_for_nonexistent_code(): void
    {
        $response = $this
            ->actingAs($this->superAdmin)
            ->delete(route('super-admin.promo-codes.destroy', 99999));

        $response->assertNotFound();
    }
}
