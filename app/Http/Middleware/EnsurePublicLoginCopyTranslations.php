<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePublicLoginCopyTranslations
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('localizer.supported_locales', [
            'ar', 'en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id',
        ]);

        $requestedLocale = strtolower((string) $request->segment(1));
        $locale = in_array($requestedLocale, $supportedLocales, true)
            ? $requestedLocale
            : app()->getLocale();

        $translations = [
            'ar' => [
                'messages.forgot_password' => 'نسيت كلمة المرور؟',
                'messages.toggle_theme' => 'تغيير المظهر',
                'messages.back_to_workspace' => 'العودة إلى مساحة العمل',
            ],
            'en' => [
                'messages.forgot_password' => 'Forgot your password?',
                'messages.toggle_theme' => 'Toggle theme',
                'messages.back_to_workspace' => 'Back to workspace',
            ],
            'fr' => [
                'messages.forgot_password' => 'Mot de passe oublié ?',
                'messages.toggle_theme' => 'Changer de thème',
                'messages.back_to_workspace' => 'Retour à l’espace de travail',
            ],
            'es' => [
                'messages.forgot_password' => '¿Olvidaste tu contraseña?',
                'messages.toggle_theme' => 'Cambiar tema',
                'messages.back_to_workspace' => 'Volver al espacio de trabajo',
            ],
            'de' => [
                'messages.forgot_password' => 'Passwort vergessen?',
                'messages.toggle_theme' => 'Darstellung wechseln',
                'messages.back_to_workspace' => 'Zurück zum Arbeitsbereich',
            ],
            'it' => [
                'messages.forgot_password' => 'Hai dimenticato la password?',
                'messages.toggle_theme' => 'Cambia tema',
                'messages.back_to_workspace' => 'Torna all’area di lavoro',
            ],
            'pt' => [
                'messages.forgot_password' => 'Esqueceu a palavra-passe?',
                'messages.toggle_theme' => 'Alterar tema',
                'messages.back_to_workspace' => 'Voltar ao espaço de trabalho',
            ],
            'ru' => [
                'messages.forgot_password' => 'Забыли пароль?',
                'messages.toggle_theme' => 'Сменить тему',
                'messages.back_to_workspace' => 'Вернуться в рабочее пространство',
            ],
            'zh' => [
                'messages.forgot_password' => '忘记密码？',
                'messages.toggle_theme' => '切换主题',
                'messages.back_to_workspace' => '返回工作区',
            ],
            'ja' => [
                'messages.forgot_password' => 'パスワードをお忘れですか？',
                'messages.toggle_theme' => 'テーマを切り替える',
                'messages.back_to_workspace' => 'ワークスペースに戻る',
            ],
            'tr' => [
                'messages.forgot_password' => 'Şifrenizi mi unuttunuz?',
                'messages.toggle_theme' => 'Temayı değiştir',
                'messages.back_to_workspace' => 'Çalışma alanına dön',
            ],
            'hi' => [
                'messages.forgot_password' => 'पासवर्ड भूल गए?',
                'messages.toggle_theme' => 'थीम बदलें',
                'messages.back_to_workspace' => 'वर्कस्पेस पर वापस जाएं',
            ],
            'ko' => [
                'messages.forgot_password' => '비밀번호를 잊으셨나요?',
                'messages.toggle_theme' => '테마 전환',
                'messages.back_to_workspace' => '워크스페이스로 돌아가기',
            ],
            'nl' => [
                'messages.forgot_password' => 'Wachtwoord vergeten?',
                'messages.toggle_theme' => 'Thema wijzigen',
                'messages.back_to_workspace' => 'Terug naar werkruimte',
            ],
            'id' => [
                'messages.forgot_password' => 'Lupa kata sandi?',
                'messages.toggle_theme' => 'Ganti tema',
                'messages.back_to_workspace' => 'Kembali ke ruang kerja',
            ],
        ];

        if (isset($translations[$locale])) {
            Lang::addLines($translations[$locale], $locale);
        }

        return $next($request);
    }
}
