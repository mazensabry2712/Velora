<?php

declare(strict_types=1);

namespace Tests\Unit\Subscription;

use App\Domain\Subscription\SubscriptionLifecycle;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    public function test_trial_period_is_exactly_seven_days(): void
    {
        $start = Carbon::parse('2026-08-28 12:00:00');
        $trialEnds = $start->copy()->addDays(SubscriptionLifecycle::TRIAL_DAYS);

        $this->assertSame('2026-09-04 12:00:00', $trialEnds->toDateTimeString());
    }

    public function test_read_only_ends_fourteen_days_after_trial_end(): void
    {
        $trialEnds = Carbon::parse('2026-09-04 12:00:00');

        $this->assertSame(
            '2026-09-18 12:00:00',
            SubscriptionLifecycle::readOnlyEndsAt($trialEnds)->toDateTimeString()
        );
    }

    public function test_deletion_is_thirty_days_after_locked_starts(): void
    {
        $trialEnds = Carbon::parse('2026-09-04 12:00:00');

        $this->assertSame(
            '2026-10-18 12:00:00',
            SubscriptionLifecycle::deletionAt($trialEnds)->toDateTimeString()
        );
    }

    public function test_total_trial_to_permanent_deletion_window_is_fifty_one_days(): void
    {
        $start = Carbon::parse('2026-08-28 12:00:00');
        $trialEnds = $start->copy()->addDays(SubscriptionLifecycle::TRIAL_DAYS);
        $deletionAt = SubscriptionLifecycle::deletionAt($trialEnds);

        $this->assertSame(51, $start->diffInDays($deletionAt));
    }
}
