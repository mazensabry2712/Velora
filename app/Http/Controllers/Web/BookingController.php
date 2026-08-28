<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\PublicBookingController;
use App\Http\Requests\Tenant\PublicBookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Compatibility adapter for the legacy web booking route.
 *
 * Rate limiting is intentionally performed before FormRequest validation so
 * repeated malformed requests cannot bypass the public booking throttle.
 * Booking business logic remains centralized in Tenant\PublicBookingController.
 */
final class BookingController extends Controller
{
    public function __construct(
        private readonly PublicBookingController $publicBookingController,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $tenantId = (string) tenant()->getTenantKey();
        $rateLimitKey = 'public-booking:' . $tenantId . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many booking attempts. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $formRequest = PublicBookingRequest::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        return $this->publicBookingController->store($formRequest);
    }
}
