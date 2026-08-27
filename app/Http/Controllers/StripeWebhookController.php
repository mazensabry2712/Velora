<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Billing\Actions\HandleStripeWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class StripeWebhookController extends Controller
{
    public function __construct(private readonly HandleStripeWebhook $handleStripeWebhook) {}

    public function handle(Request $request)
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            Log::warning('Stripe webhook: missing signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            $result = $this->handleStripeWebhook->execute($request->getContent(), $signature);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Stripe webhook rejected', ['reason' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json($result, 200);
    }
}
