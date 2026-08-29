<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Customer;

use App\Application\Customer\Actions\GetCustomerStatistics;
use App\Domain\Customer\Contracts\CustomerReader;
use Mockery;
use Tests\TestCase;

final class GetCustomerStatisticsTest extends TestCase
{
    public function test_it_delegates_customer_statistics_to_the_reader(): void
    {
        $expected = [
            'total_appointments' => 4,
            'completed' => 2,
            'cancelled' => 1,
            'no_show' => 1,
            'avg_rating' => 4.5,
            'total_spent' => 1200,
            'last_visit_at' => null,
            'ltv_tier' => 'gold',
        ];

        $reader = Mockery::mock(CustomerReader::class);
        $reader->expects('getStatistics')->with(7)->once()->andReturn($expected);

        $result = (new GetCustomerStatistics($reader))->execute(7);

        $this->assertSame($expected, $result);
    }
}
