<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use Carbon\CarbonInterface;

final class SubscriptionLifecycle
{
    public const TRIAL_DAYS = 7;
    public const READ_ONLY_DAYS = 14;
    public const LOCKED_DAYS = 6;

    public static function readOnlyEndsAt(CarbonInterface $trialEndsAt): CarbonInterface
    {
        return $trialEndsAt->copy()->addDays(self::READ_ONLY_DAYS);
    }

    public static function deletionAt(CarbonInterface $trialEndsAt): CarbonInterface
    {
        return self::readOnlyEndsAt($trialEndsAt)->addDays(self::LOCKED_DAYS);
    }

    public static function lockedAt(CarbonInterface $trialEndsAt): CarbonInterface
    {
        return self::readOnlyEndsAt($trialEndsAt);
    }
}
