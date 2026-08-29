<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkspaceFinderUiLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_workspace_finder_uses_workspace_copy(): void
    {
        app()->setLocale('en');

        $html = view('landing.find-account', [
            'baseDomain' => 'velora.test',
        ])->render();

        $this->assertStringContainsString('Find your workspace', $html);
        $this->assertStringContainsString('Enter your business workspace name to continue.', $html);
        $this->assertStringContainsString('Workspace name', $html);
        $this->assertStringContainsString('Continue', $html);
        $this->assertStringNotContainsString('Find your account', $html);
        $this->assertStringNotContainsString('Enter your email to receive account instructions.', $html);
        $this->assertStringNotContainsString('Email address', $html);
        $this->assertStringNotContainsString('Send instructions', $html);
    }

    public function test_arabic_workspace_finder_uses_arabic_copy(): void
    {
        app()->setLocale('ar');

        $html = view('landing.find-account', [
            'baseDomain' => 'velora.test',
        ])->render();

        $this->assertStringContainsString('ابحث عن مساحة عملك', $html);
        $this->assertStringContainsString('أدخل اسم مساحة العمل الخاصة بنشاطك للمتابعة.', $html);
        $this->assertStringContainsString('اسم مساحة العمل', $html);
        $this->assertStringContainsString('متابعة', $html);
        $this->assertStringNotContainsString('Find your account', $html);
        $this->assertStringNotContainsString('Enter your email to receive account instructions.', $html);
        $this->assertStringNotContainsString('Email address', $html);
        $this->assertStringNotContainsString('Send instructions', $html);
    }
}
