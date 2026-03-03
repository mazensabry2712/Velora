<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GdprConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GdprController — manage GDPR consents, data exports, and deletion requests.
 *
 * Routes (all under admin/api, Admin Tenant role only):
 *   GET    /gdpr/consents
 *   GET    /gdpr/customers/{customerId}/consents
 *   POST   /gdpr/customers/{customerId}/consents
 *   DELETE /gdpr/customers/{customerId}/consents/{type}
 *   POST   /gdpr/customers/{customerId}/export
 *   POST   /gdpr/customers/{customerId}/delete
 */
class GdprController extends Controller
{
    // Allowed consent types
    private const CONSENT_TYPES = [
        'marketing',
        'analytics',
        'functional',
        'data_processing',
        'newsletter',
        'sms',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Consent Management
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/gdpr/consents
     * Paginated list of all consent records (filterable by type, granted).
     */
    public function consents(Request $request): JsonResponse
    {
        $query = GdprConsent::with('customer:id,first_name,last_name,email')
            ->orderByDesc('granted_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('granted')) {
            $query->where('granted', (bool) $request->granted);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(50),
        ]);
    }

    /**
     * GET /admin/api/gdpr/customers/{customerId}/consents
     * All consent records for a specific customer.
     */
    public function customerConsents(int $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $consents = GdprConsent::where('customer_id', $customer->id)->get();

        return response()->json([
            'success' => true,
            'data'    => $consents,
        ]);
    }

    /**
     * POST /admin/api/gdpr/customers/{customerId}/consents
     * Record a new consent grant (or re-grant).
     *
     * Body: { "type":"marketing", "granted":true, "source":"admin", "legal_basis":"consent" }
     */
    public function recordConsent(Request $request, int $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        $validated = $request->validate([
            'type'        => 'required|string|in:' . implode(',', self::CONSENT_TYPES),
            'granted'     => 'required|boolean',
            'source'      => 'nullable|string|max:100',
            'legal_basis' => 'nullable|string|max:100',
        ]);

        $consent = GdprConsent::create([
            'customer_id' => $customer->id,
            'type'        => $validated['type'],
            'granted'     => $validated['granted'],
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'source'      => $validated['source']      ?? 'admin',
            'legal_basis' => $validated['legal_basis'] ?? 'consent',
            'granted_at'  => $validated['granted'] ? now() : null,
            'revoked_at'  => $validated['granted'] ? null : now(),
        ]);

        // Sync top-level customer.gdpr_consent flag
        if ($validated['type'] === 'data_processing') {
            $customer->update([
                'gdpr_consent'    => $validated['granted'],
                'gdpr_consent_at' => $validated['granted'] ? now() : $customer->gdpr_consent_at,
                'gdpr_consent_ip' => $validated['granted'] ? $request->ip() : $customer->gdpr_consent_ip,
            ]);
        }

        return response()->json(['success' => true, 'data' => $consent], 201);
    }

    /**
     * DELETE /admin/api/gdpr/customers/{customerId}/consents/{type}
     * Revoke a specific consent type for a customer.
     */
    public function revokeConsent(int $customerId, string $type): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        GdprConsent::where('customer_id', $customer->id)
            ->where('type', $type)
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'granted' => false]);

        if ($type === 'data_processing') {
            $customer->update(['gdpr_consent' => false]);
        }

        return response()->json(['success' => true, 'message' => __('Consent revoked')]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Data Export
    // ─────────────────────────────────────────────────────────────────────

    /**
     * POST /admin/api/gdpr/customers/{customerId}/export
     * Generate and return a JSON data export for the customer (Art. 20 GDPR).
     */
    public function requestExport(int $customerId): JsonResponse
    {
        $customer = Customer::with([
            'appointments',
            'waitingList',
            'gdprConsents',
        ])->findOrFail($customerId);

        $export = [
            'customer'    => $customer->only([
                'id', 'first_name', 'last_name', 'email', 'phone',
                'dob', 'gender', 'language', 'timezone',
                'gdpr_consent', 'gdpr_consent_at', 'created_at',
            ]),
            'appointments' => $customer->appointments->map(fn ($a) => $a->only([
                'id', 'starts_at', 'status', 'notes', 'created_at',
            ])),
            'waiting_list' => $customer->waitingList->map(fn ($w) => $w->only([
                'id', 'service_id', 'preferred_date', 'status', 'created_at',
            ])),
            'consents' => $customer->gdprConsents->map(fn ($c) => $c->only([
                'type', 'granted', 'granted_at', 'revoked_at', 'source', 'legal_basis',
            ])),
            'exported_at' => now()->toIso8601String(),
        ];

        Log::info("GDPR export generated for customer #{$customerId}");

        return response()->json([
            'success' => true,
            'message' => __('Data export generated'),
            'data'    => $export,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Deletion / Anonymisation
    // ─────────────────────────────────────────────────────────────────────

    /**
     * POST /admin/api/gdpr/customers/{customerId}/delete
     * Anonymise the customer's PII (right to erasure, Art. 17 GDPR).
     *
     * Body: { "confirm": true }
     */
    public function requestDeletion(Request $request, int $customerId): JsonResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $customer = Customer::findOrFail($customerId);

        DB::transaction(function () use ($customer) {
            // Anonymise PII
            $customer->update([
                'first_name'      => 'Deleted',
                'last_name'       => 'User',
                'email'           => 'deleted_' . $customer->id . '@removed.local',
                'phone'           => null,
                'phone_country'   => null,
                'dob'             => null,
                'gender'          => null,
                'avatar'          => null,
                'notes'           => null,
                'tags'            => null,
                'metadata'        => null,
                'gdpr_consent'    => false,
                'gdpr_consent_at' => null,
                'gdpr_consent_ip' => null,
            ]);

            // Revoke all active consents
            GdprConsent::where('customer_id', $customer->id)
                ->where('granted', true)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'granted' => false]);

            // Soft-delete the customer record
            $customer->delete();
        });

        Log::info("GDPR deletion (anonymisation) executed for customer #{$customerId}");

        return response()->json([
            'success' => true,
            'message' => __('Customer data has been anonymised and deleted'),
        ]);
    }
}
