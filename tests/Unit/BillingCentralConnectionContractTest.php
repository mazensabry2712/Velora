<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BillingCentralConnectionContractTest extends TestCase
{
    #[Test]
    public function billing_portal_uses_the_configured_central_connection(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/BillingController.php'));

        self::assertIsString($source);

        $start = strpos($source, 'public function portal(');
        $end = strpos($source, 'public function extendTrial(', $start === false ? 0 : $start);

        self::assertNotFalse($start, 'BillingController::portal() was not found.');
        self::assertNotFalse($end, 'BillingController::extendTrial() boundary was not found.');

        $portalSource = substr($source, $start, $end - $start);

        self::assertStringContainsString(
            "config('tenancy.database.central_connection', 'mysql')",
            $portalSource,
        );
        self::assertStringNotContainsString(
            "DB::connection('mysql')",
            $portalSource,
        );
    }
}
