<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\PublicBookingController;
use App\Http\Requests\Tenant\PublicBookingRequest;
use Illuminate\Http\JsonResponse;

/**
 * Compatibility adapter for the legacy web booking route.
 *
 * Public booking business logic remains centralized in
 * Tenant\PublicBookingController so the web route cannot drift from the
 * canonical validation, rate limiting, and booking action flow.
 */
final class BookingController extends Controller
{
    public function __construct(
        private readonly PublicBookingController $publicBookingController,
    ) {}

    public function store(PublicBookingRequest $request): JsonResponse
    {
        return $this->publicBookingController->store($request);
    }
}
