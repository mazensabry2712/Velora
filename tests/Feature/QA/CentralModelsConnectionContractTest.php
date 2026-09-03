<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\CountryPricing;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\TenantSubscription;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature')]
#[Group('qa')]
#[Group('tenancy')]
final class CentralModelsConnectionContractTest extends TestCase
{
    #[Test]
    public function all_global_billing_and_pricing_models_use_the_canonical_central_connection(): void
    {
        $central = (string) config('tenancy.database.central_connection', 'mysql');

        self::assertSame($central, (new SystemSetting)->getConnectionName());
        self::assertSame($central, (new CountryPricing)->getConnectionName());
        self::assertSame($central, (new SubscriptionPlan)->getConnectionName());
        self::assertSame($central, (new TenantSubscription)->getConnectionName());
    }
}
