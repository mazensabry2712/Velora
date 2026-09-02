<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('qa')]
#[Group('architecture')]
final class CanonicalSchemaTest extends TenantTestCase
{
    #[Test]
    public function staff_services_uses_staff_identity_only(): void
    {
        $this->assertTrue(Schema::hasColumn('staff_services', 'staff_id'));
        $this->assertFalse(Schema::hasColumn('staff_services', 'user_id'));
    }

    #[Test]
    public function appointment_customer_and_staff_columns_preserve_current_new_identity_until_reconciliation(): void
    {
        $this->assertTrue(Schema::hasColumn('appointments', 'customer_id_new'));
        $this->assertTrue(Schema::hasColumn('appointments', 'staff_id_new'));
        $this->assertTrue(Schema::hasColumn('appointments', 'customer_id'));
        $this->assertTrue(Schema::hasColumn('appointments', 'staff_id'));
    }
}
