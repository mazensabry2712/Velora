<?php

namespace Tests\Feature\SuperAdmin;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\SuperAdminTestCase;

#[Group('feature')]
#[Group('super-admin')]
#[Group('tenant-api')]
class TenantApiTest extends SuperAdminTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // Helpers
    // ════════════════════════════════════════════════════════════════════════

    private function insertTenant(string $id, string $name, bool $active = true): void
    {
        DB::table('tenants')->insert([
            'id'         => $id,
            'data'       => json_encode(['name' => $name, 'active' => $active]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Auth guards
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/super-admin/tenants')
             ->assertUnauthorized();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Index
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_list_all_tenants(): void
    {
        $this->insertTenant('t-list-1', 'Alpha Clinic');
        $this->insertTenant('t-list-2', 'Beta Clinic');

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants')
                     ->assertOk()
                     ->assertJsonPath('success', true)
                     ->json('data');

        $this->assertCount(2, $data);
    }

    #[Test]
    public function index_returns_empty_array_when_no_tenants(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants')
                     ->assertOk()
                     ->json('data');

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Show
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_view_a_single_tenant(): void
    {
        $this->insertTenant('t-show-1', 'Show Clinic');

        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/tenants/t-show-1')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function show_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/tenants/no-such-tenant')
             ->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Update
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_update_tenant_name(): void
    {
        $this->insertTenant('t-update-1', 'Old Name');

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-update-1', ['name' => 'New Name'])
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function admin_can_deactivate_tenant(): void
    {
        $this->insertTenant('t-deact-1', 'Active Clinic', true);

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-deact-1', ['active' => false])
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function update_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/nonexistent', ['name' => 'New'])
             ->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Destroy
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_delete_a_tenant(): void
    {
        $this->insertTenant('t-del-1', 'Deleted Clinic');

        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-del-1')
             ->assertOk()
             ->assertJsonPath('success', true);

        // Soft delete: row still exists but has deleted_at set
        $row = DB::table('tenants')->where('id', 't-del-1')->first();
        $this->assertNotNull($row, 'Soft-deleted row should still exist in DB');
        $this->assertNotNull($row->deleted_at, 'deleted_at should be set after soft delete');
    }

    #[Test]
    public function destroy_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/no-such-tenant')
             ->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Toggle Status
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_toggle_tenant_status(): void
    {
        $this->insertTenant('t-toggle-1', 'Toggle Clinic', true);

        $response = $this->actingAs($this->superAdmin)
                         ->postJson('/api/super-admin/tenants/t-toggle-1/toggle-status')
                         ->assertOk()
                         ->assertJsonPath('success', true);

        $this->assertArrayHasKey('active', $response->json('data'));
    }

    #[Test]
    public function toggle_status_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/no-such-tenant/toggle-status')
             ->assertNotFound();
    }

    #[Test]
    public function toggle_flips_active_tenant_to_inactive(): void
    {
        $this->insertTenant('t-flip-1', 'Flip Clinic', true);

        $response = $this->actingAs($this->superAdmin)
                         ->postJson('/api/super-admin/tenants/t-flip-1/toggle-status')
                         ->assertOk()
                         ->assertJsonPath('success', true);

        $this->assertFalse($response->json('data.active'));
    }

    #[Test]
    public function toggle_flips_inactive_tenant_to_active(): void
    {
        $this->insertTenant('t-flip-2', 'Dormant Clinic', false);

        $response = $this->actingAs($this->superAdmin)
                         ->postJson('/api/super-admin/tenants/t-flip-2/toggle-status')
                         ->assertOk()
                         ->assertJsonPath('success', true);

        $this->assertTrue($response->json('data.active'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // Update — validation
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function update_validates_name_cannot_exceed_255_characters(): void
    {
        $this->insertTenant('t-val-1', 'Valid Clinic');

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-val-1', [
                 'name' => str_repeat('a', 256),
             ])
             ->assertUnprocessable();
    }

    #[Test]
    public function admin_can_reactivate_a_deactivated_tenant(): void
    {
        $this->insertTenant('t-react-1', 'Suspended Clinic', false);

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-react-1', ['active' => true])
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Update — domain
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_update_tenant_domain(): void
    {
        $this->insertTenant('t-dom-1', 'Domain Clinic');

        DB::table('domains')->insert([
            'domain'    => 'old.velora.test',
            'tenant_id' => 't-dom-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-dom-1', [
                 'domain' => 'new.velora.test',
             ])
             ->assertOk()
             ->assertJsonPath('success', true);

        $this->assertDatabaseHas('domains', ['domain' => 'new.velora.test', 'tenant_id' => 't-dom-1']);
        $this->assertDatabaseMissing('domains', ['domain' => 'old.velora.test']);
    }

    #[Test]
    public function updating_domain_removes_old_domain_records(): void
    {
        $this->insertTenant('t-dom-2', 'Multi Domain Clinic');

        foreach (['a.velora.test', 'b.velora.test'] as $domain) {
            DB::table('domains')->insert([
                'domain'    => $domain,
                'tenant_id' => 't-dom-2',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-dom-2', [
                 'domain' => 'primary.velora.test',
             ])
             ->assertOk();

        $this->assertSame(
            1,
            DB::table('domains')->where('tenant_id', 't-dom-2')->count(),
            'Old domain records should be removed on update'
        );
        $this->assertDatabaseHas('domains', ['domain' => 'primary.velora.test']);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Index ordering
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function index_returns_tenants_ordered_by_latest_created(): void
    {
        DB::table('tenants')->insert([
            'id'         => 't-order-1',
            'data'       => json_encode(['name' => 'First Clinic', 'active' => true]),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        DB::table('tenants')->insert([
            'id'         => 't-order-2',
            'data'       => json_encode(['name' => 'Second Clinic', 'active' => true]),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
        DB::table('tenants')->insert([
            'id'         => 't-order-3',
            'data'       => json_encode(['name' => 'Third Clinic', 'active' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants')
                     ->assertOk()
                     ->json('data');

        $this->assertSame('t-order-3', $data[0]['id'], 'Latest tenant should appear first');
        $this->assertSame('t-order-1', $data[2]['id'], 'Oldest tenant should appear last');
    }

    // ════════════════════════════════════════════════════════════════════════
    // Reset Admin Password
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function reset_admin_password_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/no-such-id/reset-admin-password')
             ->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // ActivityLog — store / update / destroy / toggleStatus
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function update_writes_activity_log_entry(): void
    {
        $this->insertTenant('t-log-upd-1', 'Log Clinic');

        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-log-upd-1', ['name' => 'Log Clinic Updated'])
             ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'updated',
            'user_id'     => $this->superAdmin->id,
        ]);

        $log = DB::table('activity_logs')->where('action', 'updated')->first();
        $this->assertStringContainsString('Log Clinic Updated', $log->description);
    }

    #[Test]
    public function destroy_writes_activity_log_entry(): void
    {
        $this->insertTenant('t-log-del-1', 'Delete Me Clinic');

        DB::table('domains')->insert([
            'domain'     => 'deleteme.velora.test',
            'tenant_id'  => 't-log-del-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-log-del-1')
             ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'deleted',
            'user_id' => $this->superAdmin->id,
        ]);

        // Log must be written BEFORE the row is gone — tenant name preserved
        $log = DB::table('activity_logs')->where('action', 'deleted')->first();
        $this->assertStringContainsString('Delete Me Clinic', $log->description);
        $this->assertStringContainsString('deleteme.velora.test', $log->description);
    }

    #[Test]
    public function toggle_status_writes_activity_log_entry(): void
    {
        $this->insertTenant('t-log-tog-1', 'Toggle Log Clinic', true);

        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/t-log-tog-1/toggle-status')
             ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'toggle_status',
            'user_id' => $this->superAdmin->id,
        ]);

        $log = DB::table('activity_logs')->where('action', 'toggle_status')->first();
        // Active → deactivated
        $this->assertStringContainsString('deactivated', $log->description);
        $this->assertStringContainsString('Toggle Log Clinic', $log->description);
    }

    #[Test]
    public function toggle_status_logs_activated_when_flipping_inactive_tenant(): void
    {
        $this->insertTenant('t-log-tog-2', 'Dormant Log Clinic', false);

        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/t-log-tog-2/toggle-status')
             ->assertOk();

        $log = DB::table('activity_logs')->where('action', 'toggle_status')->first();
        $this->assertStringContainsString('activated', $log->description);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Domain uniqueness — can re-save same domain without 422
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function update_allows_saving_with_same_domain(): void
    {
        $this->insertTenant('t-same-dom-1', 'Same Domain Clinic');

        DB::table('domains')->insert([
            'domain'     => 'same.velora.test',
            'tenant_id'  => 't-same-dom-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sending the exact same domain must NOT return 422
        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-same-dom-1', [
                 'name'   => 'Same Domain Clinic Renamed',
                 'domain' => 'same.velora.test',
             ])
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function update_rejects_domain_already_used_by_another_tenant(): void
    {
        $this->insertTenant('t-dup-dom-1', 'Owner Clinic');
        $this->insertTenant('t-dup-dom-2', 'Thief Clinic');

        DB::table('domains')->insert([
            'domain'     => 'taken.velora.test',
            'tenant_id'  => 't-dup-dom-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // t-dup-dom-2 tries to claim taken.velora.test
        $this->actingAs($this->superAdmin)
             ->putJson('/api/super-admin/tenants/t-dup-dom-2', [
                 'domain' => 'taken.velora.test',
             ])
             ->assertUnprocessable();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Statistics endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function statistics_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/tenants/no-such-tenant/statistics')
             ->assertNotFound();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Trash — soft delete behaviour
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function destroy_soft_deletes_tenant_instead_of_hard_delete(): void
    {
        $this->insertTenant('t-soft-1', 'Soft Clinic');

        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-soft-1')
             ->assertOk()
             ->assertJsonPath('success', true);

        // Row is still in the table (soft-deleted)
        $this->assertDatabaseHas('tenants', ['id' => 't-soft-1']);

        // But it has a deleted_at timestamp
        $row = DB::table('tenants')->where('id', 't-soft-1')->first();
        $this->assertNotNull($row->deleted_at, 'deleted_at should be set after soft delete');
    }

    #[Test]
    public function soft_deleted_tenant_does_not_appear_in_index(): void
    {
        $this->insertTenant('t-soft-vis-1', 'Visible Clinic');
        $this->insertTenant('t-soft-vis-2', 'Hidden Clinic');

        // Soft-delete the second one
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-soft-vis-2')
             ->assertOk();

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants')
                     ->assertOk()
                     ->json('data');

        $ids = collect($data)->pluck('id')->all();
        $this->assertContains('t-soft-vis-1', $ids);
        $this->assertNotContains('t-soft-vis-2', $ids, 'Soft-deleted tenant must not appear in index');
    }

    #[Test]
    public function trash_endpoint_returns_only_soft_deleted_tenants(): void
    {
        $this->insertTenant('t-trash-act-1', 'Active One');
        $this->insertTenant('t-trash-del-1', 'Trashed One');

        // Soft-delete one
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-trash-del-1')
             ->assertOk();

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants/trash')
                     ->assertOk()
                     ->assertJsonPath('success', true)
                     ->json('data');

        $ids = collect($data)->pluck('id')->all();
        $this->assertContains('t-trash-del-1', $ids);
        $this->assertNotContains('t-trash-act-1', $ids, 'Active tenant must not appear in trash');
    }

    #[Test]
    public function trash_endpoint_returns_empty_when_no_deletes(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants/trash')
                     ->assertOk()
                     ->json('data');

        $this->assertEmpty($data);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Restore
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_restore_a_soft_deleted_tenant(): void
    {
        $this->insertTenant('t-restore-1', 'Restore Clinic');

        // Delete first
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-restore-1')
             ->assertOk();

        // Now restore
        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/t-restore-1/restore')
             ->assertOk()
             ->assertJsonPath('success', true);

        // deleted_at must be null again
        $row = DB::table('tenants')->where('id', 't-restore-1')->first();
        $this->assertNull($row->deleted_at, 'deleted_at should be null after restore');
    }

    #[Test]
    public function restored_tenant_reappears_in_index(): void
    {
        $this->insertTenant('t-reapp-1', 'Reappear Clinic');

        $this->actingAs($this->superAdmin)->deleteJson('/api/super-admin/tenants/t-reapp-1');
        $this->actingAs($this->superAdmin)->postJson('/api/super-admin/tenants/t-reapp-1/restore');

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/tenants')
                     ->assertOk()
                     ->json('data');

        $this->assertContains('t-reapp-1', collect($data)->pluck('id')->all());
    }

    #[Test]
    public function restore_returns_404_for_non_trashed_tenant(): void
    {
        // Tenant exists but is NOT in trash
        $this->insertTenant('t-nottrash-1', 'Normal Clinic');

        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/t-nottrash-1/restore')
             ->assertNotFound();
    }

    #[Test]
    public function restore_writes_activity_log_entry(): void
    {
        $this->insertTenant('t-log-restore-1', 'Log Restore Clinic');

        $this->actingAs($this->superAdmin)->deleteJson('/api/super-admin/tenants/t-log-restore-1');

        $this->actingAs($this->superAdmin)
             ->postJson('/api/super-admin/tenants/t-log-restore-1/restore')
             ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'restored',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Force Delete (permanent)
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function admin_can_force_delete_a_trashed_tenant(): void
    {
        $this->insertTenant('t-force-1', 'Force Delete Clinic');

        // Soft-delete first
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-force-1')
             ->assertOk();

        // Now force-delete
        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-force-1/force-delete')
             ->assertOk()
             ->assertJsonPath('success', true);

        // Row must be completely gone
        $this->assertDatabaseMissing('tenants', ['id' => 't-force-1']);
    }

    #[Test]
    public function force_delete_returns_404_for_non_trashed_tenant(): void
    {
        // Active tenant — should NOT be force-deletable directly
        $this->insertTenant('t-force-live-1', 'Live Clinic');

        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-force-live-1/force-delete')
             ->assertNotFound();
    }

    #[Test]
    public function force_delete_writes_activity_log_entry(): void
    {
        $this->insertTenant('t-log-force-1', 'Log Force Clinic');

        $this->actingAs($this->superAdmin)->deleteJson('/api/super-admin/tenants/t-log-force-1');

        $this->actingAs($this->superAdmin)
             ->deleteJson('/api/super-admin/tenants/t-log-force-1/force-delete')
             ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'force_deleted',
            'user_id' => $this->superAdmin->id,
        ]);

        $log = DB::table('activity_logs')->where('action', 'force_deleted')->first();
        $this->assertStringContainsString('Log Force Clinic', $log->description);
    }
}
