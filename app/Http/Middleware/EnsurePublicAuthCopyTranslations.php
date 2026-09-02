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
                'Forgot your password?' => 'نسيت كلمة المرور؟',
            ],
            'en' => [
                'landing.signup_business_type' => 'What type of business is this?',
            ],
            'fr' => [
                'landing.signup_business_type' => 'Quel est le type de votre entreprise ?',
                'Forgot your password?' => 'Mot de passe oublié ?',
            ],
            'es' => [
                'landing.signup_business_type' => '¿Qué tipo de negocio es?',
                'Forgot your password?' => '¿Olvidaste tu contraseña?',
            ],
            'de' => [
                'landing.signup_business_type' => 'Welche Art von Unternehmen ist das?',
                'Forgot your password?' => 'Passwort vergessen?',
            ],
            'it' => [
                'landing.signup_business_type' => 'Che tipo di attività è?',
                'Forgot your password?' => 'Hai dimenticato la password?',
            ],
            'pt' => [
                'landing.signup_business_type' => 'Que tipo de negócio é este?',
                'Forgot your password?' => 'Esqueceu a palavra-passe?',
            ],
            'ru' => [
                'landing.signup_business_type' => 'Какой это тип бизнеса?',
                'Forgot your password?' => 'Забыли пароль?',
            ],
            'zh' => [
                'landing.signup_business_type' => '这是什么类型的企业？',
                'Forgot your password?' => '忘记密码？',
            ],
            'ja' => [
                'landing.signup_business_type' => 'どのような種類のビジネスですか？',
                'Forgot your password?' => 'パスワードをお忘れですか？',
            ],
            'tr' => [
                'landing.signup_business_type' => 'Bu ne tür bir işletme?',
                'Forgot your password?' => 'Şifrenizi mi unuttunuz?',
            ],
            'hi' => [
                'landing.signup_business_type' => 'यह किस प्रकार का व्यवसाय है?',
                'Forgot your password?' => 'पासवर्ड भूल गए?',
            ],
            'ko' => [
                'landing.signup_business_type' => '어떤 유형의 비즈니스인가요?',
                'Forgot your password?' => '비밀번호를 잊으셨나요?',
            ],
            'nl' => [
                'landing.signup_business_type' => 'Wat voor soort bedrijf is dit?',
                'Forgot your password?' => 'Wachtwoord vergeten?',
            ],
            'id' => [
                'landing.signup_business_type' => 'Jenis bisnis apa ini?',
                'Forgot your password?' => 'Lupa kata sandi?',
            ],
        ];

        if (isset($translations[$locale])) {
            Lang::addLines($translations[$locale], $locale);
        }

        return $next($request);
    }
}
