<?php

declare(strict_types=1);

namespace App\Domain\Landing\Contracts;

interface LandingSettingsReader
{
    /** @return array{appName:string,appLogoUrl:string,registrationEnabled:bool,defaultTrialDays:int} */
    public function read(): array;
}
