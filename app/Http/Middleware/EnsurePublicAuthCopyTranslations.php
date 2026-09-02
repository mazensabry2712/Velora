<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePublicAuthCopyTranslations
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = strtolower((string) ($request->segment(1) ?: app()->getLocale()));

        $translations = [
            'ar' => [
                'landing.signup_business_type' => 'ما نوع النشاط التجاري؟',
            ],
            'en' => [
                'landing.signup_business_type' => 'What type of business is this?',
            ],
            'fr' => [
                'landing.signup_business_type' => 'Quel est le type de votre entreprise ?',
            ],
            'es' => [
                'landing.signup_business_type' => '¿Qué tipo de negocio es?',
            ],
            'de' => [
                'landing.signup_business_type' => 'Welche Art von Unternehmen ist das?',
            ],
            'it' => [
                'landing.signup_business_type' => 'Che tipo di attività è?',
            ],
            'pt' => [
                'landing.signup_business_type' => 'Que tipo de negócio é este?',
            ],
            'ru' => [
                'landing.signup_business_type' => 'Какой это тип бизнеса?',
            ],
            'zh' => [
                'landing.signup_business_type' => '这是什么类型的企业？',
            ],
            'ja' => [
                'landing.signup_business_type' => 'どのような種類のビジネスですか？',
            ],
            'tr' => [
                'landing.signup_business_type' => 'Bu ne tür bir işletme?',
            ],
            'hi' => [
                'landing.signup_business_type' => 'यह किस प्रकार का व्यवसाय है?',
            ],
            'ko' => [
                'landing.signup_business_type' => '어떤 유형의 비즈니스인가요?',
            ],
            'nl' => [
                'landing.signup_business_type' => 'Wat voor soort bedrijf is dit?',
            ],
            'id' => [
                'landing.signup_business_type' => 'Jenis bisnis apa ini?',
            ],
        ];

        if (isset($translations[$locale])) {
            Lang::addLines($translations[$locale], $locale);
        }

        return $next($request);
    }
}
