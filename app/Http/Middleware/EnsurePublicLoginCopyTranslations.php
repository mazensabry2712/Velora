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

        $directTranslations = [
            'ar' => [
                'Toggle theme' => 'تغيير المظهر',
                'Back to workspace' => 'العودة إلى مساحة العمل',
                'Forgot your password?' => 'نسيت كلمة المرور؟',
            ],
            'en' => [
                'Toggle theme' => 'Toggle theme',
                'Back to workspace' => 'Back to workspace',
                'Forgot your password?' => 'Forgot your password?',
            ],
            'fr' => [
                'Toggle theme' => 'Changer de thème',
                'Back to workspace' => 'Retour à l’espace de travail',
                'Forgot your password?' => 'Mot de passe oublié ?',
            ],
            'es' => [
                'Toggle theme' => 'Cambiar tema',
                'Back to workspace' => 'Volver al espacio de trabajo',
                'Forgot your password?' => '¿Olvidaste tu contraseña?',
            ],
            'de' => [
                'Toggle theme' => 'Darstellung wechseln',
                'Back to workspace' => 'Zurück zum Arbeitsbereich',
                'Forgot your password?' => 'Passwort vergessen?',
            ],
            'it' => [
                'Toggle theme' => 'Cambia tema',
                'Back to workspace' => 'Torna all’area di lavoro',
                'Forgot your password?' => 'Hai dimenticato la password?',
            ],
            'pt' => [
                'Toggle theme' => 'Alterar tema',
                'Back to workspace' => 'Voltar ao espaço de trabalho',
                'Forgot your password?' => 'Esqueceu a palavra-passe?',
            ],
            'ru' => [
                'Toggle theme' => 'Сменить тему',
                'Back to workspace' => 'Вернуться в рабочее пространство',
                'Forgot your password?' => 'Забыли пароль?',
            ],
            'zh' => [
                'Toggle theme' => '切换主题',
                'Back to workspace' => '返回工作区',
                'Forgot your password?' => '忘记密码？',
            ],
            'ja' => [
                'Toggle theme' => 'テーマを切り替える',
                'Back to workspace' => 'ワークスペースに戻る',
                'Forgot your password?' => 'パスワードをお忘れですか？',
            ],
            'tr' => [
                'Toggle theme' => 'Temayı değiştir',
                'Back to workspace' => 'Çalışma alanına dön',
                'Forgot your password?' => 'Şifrenizi mi unuttunuz?',
            ],
            'hi' => [
                'Toggle theme' => 'थीम बदलें',
                'Back to workspace' => 'वर्कस्पेस पर वापस जाएं',
                'Forgot your password?' => 'पासवर्ड भूल गए?',
            ],
            'ko' => [
                'Toggle theme' => '테마 전환',
                'Back to workspace' => '워크스페이스로 돌아가기',
                'Forgot your password?' => '비밀번호를 잊으셨나요?',
            ],
            'nl' => [
                'Toggle theme' => 'Thema wijzigen',
                'Back to workspace' => 'Terug naar werkruimte',
                'Forgot your password?' => 'Wachtwoord vergeten?',
            ],
            'id' => [
                'Toggle theme' => 'Ganti tema',
                'Back to workspace' => 'Kembali ke ruang kerja',
                'Forgot your password?' => 'Lupa kata sandi?',
            ],
        ];

        $translations = $directTranslations[$locale] ?? [];

        Lang::handleMissingKeysUsing(
            static function (string $key, array $replace, ?string $missingLocale, bool $fallback) use ($translations): ?string {
                return $translations[$key] ?? null;
            }
        );

        return $next($request);
    }
}
