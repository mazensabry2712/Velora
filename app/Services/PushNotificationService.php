<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService — sends push notifications via Firebase FCM.
 *
 * Configuration (add to .env):
 *   FIREBASE_SERVER_KEY=your_legacy_server_key
 *
 * For FCM HTTP v1 (OAuth2), replace FCM_ENDPOINT / sendViaFcm() with
 * a Google OAuth2 token flow using a service-account JSON key.
 *
 * Usage:
 *   app(PushNotificationService::class)->send($token, 'Title', 'Body', ['key'=>'val']);
 *   app(PushNotificationService::class)->sendToAll($customer->pushTokens, 'Title', 'Body');
 */
class PushNotificationService
{
    protected const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Send a push notification to a single PushToken.
     *
     * @param  PushToken  $token
     * @param  string     $title
     * @param  string     $body
     * @param  array<string,mixed>  $data   Extra key-value pairs sent in the data payload.
     * @return bool  true on success, false on failure
     */
    public function send(PushToken $token, string $title, string $body, array $data = []): bool
    {
        if (! $token->is_active) {
            Log::debug("PushNotificationService: token #{$token->id} is inactive — skipped");
            return false;
        }

        return match ($token->provider ?? 'fcm') {
            'onesignal' => $this->sendViaOneSignal($token->token, $title, $body, $data),
            default     => $this->sendViaFcm($token->token, $title, $body, $data),
        };
    }

    /**
     * Send a notification to multiple tokens, ignoring inactive ones.
     *
     * @param  \Illuminate\Support\Collection<int, PushToken>  $tokens
     * @return array{sent: int, failed: int}
     */
    public function sendToAll(iterable $tokens, string $title, string $body, array $data = []): array
    {
        $sent   = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            $this->send($token, $title, $body, $data) ? $sent++ : $failed++;
        }

        return compact('sent', 'failed');
    }

    // ── Firebase FCM (Legacy HTTP API) ────────────────────────────────────────

    protected function sendViaFcm(string $deviceToken, string $title, string $body, array $data): bool
    {
        $serverKey = config('services.firebase.server_key');

        if (! $serverKey) {
            Log::warning('PushNotificationService: FIREBASE_SERVER_KEY not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post(self::FCM_ENDPOINT, [
                'to'           => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data'         => $data,
                'priority'     => 'high',
            ]);

            if (! $response->successful()) {
                Log::warning('PushNotificationService FCM error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'token'  => substr($deviceToken, 0, 10) . '…',
                ]);
                return false;
            }

            $json = $response->json();

            // FCM returns success=1 even for 200 responses; check payload
            if (($json['success'] ?? 0) < 1) {
                Log::warning('PushNotificationService FCM: success=0', ['response' => $json]);
                return false;
            }

            return true;

        } catch (ConnectionException $e) {
            Log::error('PushNotificationService FCM connection error: ' . $e->getMessage());
            return false;
        }
    }

    // ── OneSignal REST API ────────────────────────────────────────────────────

    protected function sendViaOneSignal(string $playerId, string $title, string $body, array $data): bool
    {
        $appId  = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            Log::warning('PushNotificationService: OneSignal credentials not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id'              => $appId,
                'include_player_ids'  => [$playerId],
                'headings'            => ['en' => $title],
                'contents'            => ['en' => $body],
                'data'                => $data,
            ]);

            if (! $response->successful()) {
                Log::warning('PushNotificationService OneSignal error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;

        } catch (ConnectionException $e) {
            Log::error('PushNotificationService OneSignal connection error: ' . $e->getMessage());
            return false;
        }
    }
}
