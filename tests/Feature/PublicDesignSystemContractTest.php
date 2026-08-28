<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PublicDesignSystemContractTest extends TestCase
{
    public function test_public_design_system_is_explicitly_layered_and_shared(): void
    {
        $brandCss = file_get_contents(public_path('css/velora-brand.css'));
        $publicCss = file_get_contents(public_path('css/velora-public.css'));
        $landingLayout = file_get_contents(resource_path('views/layouts/landing.blade.php'));
        $loginView = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertIsString($brandCss);
        $this->assertIsString($publicCss);
        $this->assertIsString($landingLayout);
        $this->assertIsString($loginView);

        $this->assertStringContainsString("@import url('./velora-public.css');", $brandCss);
        $this->assertStringContainsString('--velora-primary-purple', $brandCss);
        $this->assertStringContainsString('.velora-public-container', $publicCss);
        $this->assertStringContainsString('.velora-public-surface', $publicCss);
        $this->assertStringContainsString('.velora-public-button-primary', $publicCss);
        $this->assertStringContainsString("asset('css/velora-brand.css')", $landingLayout);
        $this->assertStringContainsString("asset('css/velora-brand.css')", $loginView);
    }

    public function test_public_design_system_does_not_define_a_second_brand_palette(): void
    {
        $publicCss = file_get_contents(public_path('css/velora-public.css'));

        $this->assertIsString($publicCss);
        $this->assertStringNotContainsString('#4F46E5', $publicCss);
        $this->assertStringNotContainsString('#4338CA', $publicCss);
        $this->assertStringNotContainsString('#7C3AED', $publicCss);
    }
}
