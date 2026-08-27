<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Billing\Actions\HandleMoyasarWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class MoyasarWebhookController extends Controller
{
    public function __construct(private readonly HandleMoyasarWebhook $handleMoyasarWebhook) {}

    public function handle(Request $request)
    {
        try {
            $result = $this->handleMoyasarWebhook->execute(
                $request->getContent(),
                $request->header('X-Moyasar-Signature')
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('Moyasar webhook rejected', ['reason' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Moyasar webhook processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json($result, 200);
    }
}
