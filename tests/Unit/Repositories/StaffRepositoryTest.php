<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Repositories\Eloquent\StaffRepository;
use Tests\TenantTestCase;

#[Group('unit')]
#[Group('repositories')]
class StaffRepositoryTest extends TenantTestCase
{
    private StaffRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->app->make(StaffRepository::class);
    }

    #[Test]
    public function find_by_id_returns_user(): void
    {
        $result = $this->repo->findById($this->staffMember->id);
        $this->assertNotNull($result);
        $this->assertEquals($this->staffMember->id, $result->id);
    }

    #[Test]
    public function find_by_id_returns_null_for_missing_user(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    #[Test]
    public function find_with_relations_eager_loads_staff_services(): void
    {
        $result = $this->repo->findWithRelations($this->staffMember->id, ['staffProfile.services']);
        $this->assertTrue($result->relationLoaded('staffProfile'));
        $this->assertTrue($result->staffProfile->relationLoaded('services'));
    }

    #[Test]
    public function all_returns_only_staff_role_users(): void
    {
        $results = $this->repo->all();
        foreach ($results as $user) $this->assertTrue($user->hasRole($this->staffRole));
    }

    #[Test]
    public function all_returns_at_least_one_staff_member(): void
    {
        $results = $this->repo->all();
        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    #[Test]
    public function create_persists_new_staff_user(): void
    {
        $data = ['name' => 'New Doctor', 'email' => 'newdoctor@test.com', 'phone' => '0509999999', 'specialization' => 'Dermatology'];
        $user = $this->repo->create($data);
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'newdoctor@test.com', 'specialization' => 'Dermatology']);
    }

    #[Test]
    public function create_assigns_staff_role(): void
    {
        $user = $this->repo->create(['name' => 'Dr. Auto Role', 'email' => 'drautorole@test.com', 'specialization' => 'Cardiology']);
        $this->assertTrue($user->fresh()->hasRole($this->staffRole));
    }

    #[Test]
    public function create_syncs_services_when_provided(): void
    {
        $user = $this->repo->create(
            ['name' => 'Dr. Services', 'email' => 'drservices@test.com', 'specialization' => 'ENT'],
            services: [$this->service->id],
        );

        $staff = $user->load('staffProfile.services')->staffProfile;
        $this->assertTrue($staff->services->contains($this->service->id));
    }

    #[Test]
    public function create_sets_default_password_from_email_prefix(): void
    {
        $email = 'drpassword@test.com';
        $user = $this->repo->create(['name' => 'Dr. Password', 'email' => $email, 'specialization' => 'Ortho']);
        $expectedPassword = explode('@', $email)[0] . '123';
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($expectedPassword, $user->password));
    }

    #[Test]
    public function update_changes_staff_fields(): void
    {
        $this->repo->update($this->staffMember, ['name' => $this->staffMember->name, 'email' => $this->staffMember->email, 'specialization' => 'Pediatrics']);
        $this->assertDatabaseHas('users', ['id' => $this->staffMember->id, 'specialization' => 'Pediatrics']);
    }

    #[Test]
    public function update_syncs_services(): void
    {
        $this->repo->update($this->staffMember, ['name' => $this->staffMember->name, 'email' => $this->staffMember->email], services: [$this->service->id]);
        $staff = $this->staffMember->fresh()->load('staffProfile.services')->staffProfile;
        $this->assertTrue($staff->services->contains($this->service->id));
    }

    #[Test]
    public function delete_removes_staff_member(): void
    {
        $user = $this->repo->create(['name' => 'To Be Deleted', 'email' => 'delete@test.com', 'specialization' => 'X']);
        $this->repo->delete($user);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    #[Test]
    public function get_by_specialization_filters_correctly(): void
    {
        $this->repo->create(['name' => 'Dr. Cardio', 'email' => 'cardio@test.com', 'specialization' => 'Cardiology']);
        $results = $this->repo->getBySpecialization('Cardiology');
        $this->assertGreaterThanOrEqual(1, $results->count());
        foreach ($results as $r) {
            $this->assertEquals('Cardiology', $r->specialization);
            $this->assertTrue($r->hasRole($this->staffRole));
        }
    }
}
