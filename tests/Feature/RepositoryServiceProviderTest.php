<?php

namespace Tests\Feature;

use App\Providers\RepositoryServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Eloquent\AppointmentRepository;
use App\Repositories\Eloquent\QueueRepository;
use App\Repositories\Eloquent\StaffRepository;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('ioc')]
class RepositoryServiceProviderTest extends TenantTestCase
{
    #[Test]
    public function appointment_interface_resolves_to_eloquent_implementation(): void
    {
        $resolved = app(AppointmentRepositoryInterface::class);

        $this->assertInstanceOf(AppointmentRepository::class, $resolved);
    }

    #[Test]
    public function queue_interface_resolves_to_eloquent_implementation(): void
    {
        $resolved = app(QueueRepositoryInterface::class);

        $this->assertInstanceOf(QueueRepository::class, $resolved);
    }

    #[Test]
    public function staff_interface_resolves_to_eloquent_implementation(): void
    {
        $resolved = app(StaffRepositoryInterface::class);

        $this->assertInstanceOf(StaffRepository::class, $resolved);
    }

    #[Test]
    public function each_resolution_returns_a_fresh_instance(): void
    {
        // Bindings are not singletons – each resolve returns a new object
        $first  = app(AppointmentRepositoryInterface::class);
        $second = app(AppointmentRepositoryInterface::class);

        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function service_provider_is_registered_in_bootstrap(): void
    {
        $providers = app()->getLoadedProviders();

        $this->assertArrayHasKey(RepositoryServiceProvider::class, $providers);
    }
}
