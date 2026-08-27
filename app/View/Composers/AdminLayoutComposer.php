<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Domain\Administration\Contracts\SystemNotificationReader;
use Illuminate\View\View;

final class AdminLayoutComposer
{
    public function __construct(
        private readonly SystemNotificationReader $notifications,
    ) {}

    public function compose(View $view): void
    {
        try {
            $tenantId = tenant('id');

            if (! $tenantId) {
                $view->with('systemNotifications', collect());
                return;
            }

            $view->with(
                'systemNotifications',
                $this->notifications->forTenant((string) $tenantId),
            );
        } catch (\Throwable) {
            $view->with('systemNotifications', collect());
        }
    }
}
