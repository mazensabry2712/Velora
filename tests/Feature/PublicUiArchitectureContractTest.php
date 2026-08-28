<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicUiArchitectureContractTest extends TestCase
{
    public function test_public_design_system_is_loaded_by_public_auth_and_landing_surfaces(): void
    {
        $brand = file_get_contents(base_path('public/css/velora-brand.css'));
        $public = file_get_contents(base_path('public/css/velora-public.css'));
        $landing = file_get_contents(base_path('resources/views/layouts/landing.blade.php'));
        $login = file_get_contents(base_path('resources/views/auth/login.blade.php'));
        $signup = file_get_contents(base_path('resources/views/landing/signup.blade.php'));

        $this->assertStringContainsString("@import url('./velora-public.css');", $brand);
        $this->assertStringContainsString('velora-public-container', $public);
        $this->assertStringContainsString('velora-public-surface', $public);
        $this->assertStringContainsString('velora-public-button-primary', $public);
        $this->assertStringContainsString("asset('css/velora-brand.css')", $landing);
        $this->assertStringContainsString("asset('css/velora-brand.css')", $login);
        $this->assertStringContainsString("@extends('layouts.landing')", $signup);
    }

    public function test_public_pages_do_not_define_a_second_root_brand_palette(): void
    {
        $landing = file_get_contents(base_path('resources/views/layouts/landing.blade.php'));
        $signup = file_get_contents(base_path('resources/views/landing/signup.blade.php'));

        foreach ([$landing, $signup] as $view) {
            $this->assertStringNotContainsString('--velora-primary-purple:', $view);
            $this->assertStringNotContainsString('--velora-primary-blue:', $view);
            $this->assertStringNotContainsString('--velora-gradient:', $view);
        }
    }
}
