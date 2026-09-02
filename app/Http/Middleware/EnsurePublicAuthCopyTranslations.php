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
                'Toggle theme' => 'تبديل المظهر',
                'Back to workspace' => 'العودة إلى مساحة العمل',
            ],
            'en' => [
                'landing.signup_business_type' => 'What type of business is this?',
            ],
            'fr' => [
                'landing.signup_business_type' => 'Quel est le type de votre entreprise ?',
                'Forgot your password?' => 'Mot de passe oublié ?',
                'Toggle theme' => 'Changer de thème',
                'Back to workspace' => 'Retour à l’espace de travail',
            ],
            'es' => [
                'landing.signup_business_type' => '¿Qué tipo de negocio es?',
                'Forgot your password?' => '¿Olvidaste tu contraseña?',
                'Toggle theme' => 'Cambiar tema',
                'Back to workspace' => 'Volver al espacio de trabajo',
            ],
            'de' => [
                'landing.signup_business_type' => 'Welche Art von Unternehmen ist das?',
                'Forgot your password?' => 'Passwort vergessen?',
                'Toggle theme' => 'Darstellung wechseln',
                'Back to workspace' => 'Zurück zum Arbeitsbereich',
            ],
            'it' => [
                'landing.signup_business_type' => 'Che tipo di attività è?',
                'Forgot your password?' => 'Hai dimenticato la password?',
                'Toggle theme' => 'Cambia tema',
                'Back to workspace' => 'Torna all’area di lavoro',
            ],
            'pt' => [
                'landing.signup_business_type' => 'Que tipo de negócio é este?',
                'Forgot your password?' => 'Esqueceu a palavra-passe?',
                'Toggle theme' => 'Alternar tema',
                'Back to workspace' => 'Voltar ao espaço de trabalho',
            ],
            'ru' => [
                'landing.signup_business_type' => 'Какой это тип бизнеса?',
                'Forgot your password?' => 'Забыли пароль?',
                'Toggle theme' => 'Переключить тему',
                'Back to workspace' => 'Вернуться в рабочее пространство',
            ],
            'zh' => [
                'landing.signup_business_type' => '这是什么类型的企业？',
                'Forgot your password?' => '忘记密码？',
                'Toggle theme' => '切换主题',
                'Back to workspace' => '返回工作区',
            ],
            'ja' => [
                'landing.signup_business_type' => 'どのような種類のビジネスですか？',
                'Forgot your password?' => 'パスワードをお忘れですか？',
                'Toggle theme' => 'テーマを切り替える',
                'Back to workspace' => 'ワークスペースに戻る',
            ],
            'tr' => [
                'landing.signup_business_type' => 'Bu ne tür bir işletme?',
                'Forgot your password?' => 'Şifrenizi mi unuttunuz?',
                'Toggle theme' => 'Temayı değiştir',
                'Back to workspace' => 'Çalışma alanına dön',
            ],
            'hi' => [
                'landing.signup_business_type' => 'यह किस प्रकार का व्यवसाय है?',
                'Forgot your password?' => 'पासवर्ड भूल गए?',
                'Toggle theme' => 'थीम बदलें',
                'Back to workspace' => 'वर्कस्पेस पर वापस जाएं',
            ],
            'ko' => [
                'landing.signup_business_type' => '어떤 유형의 비즈니스인가요?',
                'Forgot your password?' => '비밀번호를 잊으셨나요?',
                'Toggle theme' => '테마 전환',
                'Back to workspace' => '워크스페이스로 돌아가기',
            ],
            'nl' => [
                'landing.signup_business_type' => 'Wat voor soort bedrijf is dit?',
                'Forgot your password?' => 'Wachtwoord vergeten?',
                'Toggle theme' => 'Thema wijzigen',
                'Back to workspace' => 'Terug naar werkruimte',
            ],
            'id' => [
                'landing.signup_business_type' => 'Jenis bisnis apa ini?',
                'Forgot your password?' => 'Lupa kata sandi?',
                'Toggle theme' => 'Ganti tema',
                'Back to workspace' => 'Kembali ke ruang kerja',
            ],
        ];

        if (isset($translations[$locale])) {
            Lang::addLines($translations[$locale], $locale);
        }

        return $next($request);
    }
}
