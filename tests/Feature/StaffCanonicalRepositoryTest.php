<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('staff')]
#[Group('identity-cleanup')]
final class StaffCanonicalRepositoryTest extends TenantTestCase
{
    #[Test]
    public function staff_repository_reads_the_canonical_staff_entity(): void
    {
        $repository = app(StaffRepositoryInterface::class);

        $staff = $repository->findById($this->staff->id);

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertSame($this->staff->id, $staff?->id);
        $this->assertSame($this->staffMember->id, $staff?->user_id);
    }

    #[Test]
    public function staff_repository_relations_are_staff_owned(): void
    {
        $repository = app(StaffRepositoryInterface::class);

        $staff = $repository->findWithRelations($this->staff->id, ['user', 'services', 'workingHours']);

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertInstanceOf(User::class, $staff?->user);
        $this->assertTrue($staff?->relationLoaded('services'));
        $this->assertTrue($staff?->relationLoaded('workingHours'));
        $this->assertTrue($staff?->services->contains('id', $this->service->id));
    }

    #[Test]
    public function staff_compatibility_accessors_do_not_duplicate_identity_storage(): void
    {
        $staff = $this->staff->fresh(['user']);

        $this->assertSame('Staff Member', $staff->name);
        $this->assertSame('General', $staff->specialization);
        $this->assertSame($staff->full_name, $staff->name);
        $this->assertTrue($staff->user->is($this->staffMember));
    }
}
