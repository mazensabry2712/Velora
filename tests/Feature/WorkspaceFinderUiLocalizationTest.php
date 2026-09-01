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
        $this->assertStringContainsString('>Continue</span>', $html);
        $this->assertStringNotContainsString('Find your account', $html);
        $this->assertStringNotContainsString('Enter your email to receive account instructions.', $html);
        $this->assertStringNotContainsString('>Email address</', $html);
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
        $this->assertStringContainsString('>متابعة</span>', $html);
        $this->assertStringNotContainsString('Find your account', $html);
        $this->assertStringNotContainsString('Enter your email to receive account instructions.', $html);
        $this->assertStringNotContainsString('>Email address</', $html);
        $this->assertStringNotContainsString('Send instructions', $html);
    }

    public function test_french_workspace_finder_uses_french_ui_copy(): void
    {
        app()->setLocale('fr');

        $html = view('landing.find-account', [
            'baseDomain' => 'velora.test',
        ])->render();

        $this->assertStringContainsString('Retrouvez votre espace de travail', $html);
        $this->assertStringContainsString('Saisissez le nom de l’espace de travail de votre entreprise pour continuer.', $html);
        $this->assertStringContainsString('Nom de l’espace de travail', $html);
        $this->assertStringContainsString('>Continuer</span>', $html);
        $this->assertStringContainsString('Connexion professionnelle sécurisée', $html);
        $this->assertStringContainsString('Espaces de travail actifs', $html);
        $this->assertStringContainsString('Tous droits réservés.', $html);

        $this->assertStringNotContainsString('Features', $html);
        $this->assertStringNotContainsString('How it works', $html);
        $this->assertStringNotContainsString('Pricing', $html);
        $this->assertStringNotContainsString('Company admin sign in', $html);
        $this->assertStringNotContainsString('Start free trial', $html);
        $this->assertStringNotContainsString('All rights reserved.', $html);
        $this->assertStringNotContainsString('>Workspace name<', $html);
        $this->assertStringNotContainsString('>Continue</span>', $html);
    }
}
