<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Domain\Landing\Contracts\LandingSettingsReader;
use Illuminate\View\View;

final class LandingLayoutComposer
{
    public function __construct(
        private readonly LandingSettingsReader $settings,
    ) {}

    public function compose(View $view): void
    {
        $view->with($this->settings->read());
    }
}
