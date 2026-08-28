<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Subscription\SubscriptionLifecycle;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    public function test_canonical_lifecycle_is_27_days_from_trial_start(): void
    {
        $trialStartsAt = Carbon::create(2026, 8, 28, 10, 30, 0, 'UTC');
        $trialEndsAt = $trialStartsAt->copy()->addDays(SubscriptionLifecycle::TRIAL_DAYS);

        $readOnlyEndsAt = SubscriptionLifecycle::readOnlyEndsAt($trialEndsAt);
        $lockedAt = SubscriptionLifecycle::lockedAt($trialEndsAt);
        $deletionAt = SubscriptionLifecycle::deletionAt($trialEndsAt);

        $this->assertSame(7, $trialStartsAt->diffInDays($trialEndsAt));
        $this->assertSame(14, $trialEndsAt->diffInDays($readOnlyEndsAt));
        $this->assertTrue($readOnlyEndsAt->equalTo($lockedAt));
        $this->assertSame(6, $lockedAt->diffInDays($deletionAt));
        $this->assertSame(27, $trialStartsAt->diffInDays($deletionAt));
    }

    public function test_lifecycle_boundaries_preserve_the_same_time_of_day_and_timezone(): void
    {
        $trialEndsAt = Carbon::create(2026, 9, 4, 23, 45, 12, 'UTC');

        $readOnlyEndsAt = SubscriptionLifecycle::readOnlyEndsAt($trialEndsAt);
        $deletionAt = SubscriptionLifecycle::deletionAt($trialEndsAt);

        $this->assertSame('UTC', $readOnlyEndsAt->getTimezone()->getName());
        $this->assertSame('UTC', $deletionAt->getTimezone()->getName());
        $this->assertSame('23:45:12', $readOnlyEndsAt->format('H:i:s'));
        $this->assertSame('23:45:12', $deletionAt->format('H:i:s'));
    }
}
