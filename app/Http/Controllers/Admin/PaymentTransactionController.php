<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin — Payment Transactions
 *
 * Routes:
 *   GET    /admin/api/payments                  – paginated list + filters
 *   GET    /admin/api/payments/summary           – aggregate stats
 *   GET    /admin/api/payments/{id}              – single transaction detail
 *   PATCH  /admin/api/payments/{id}/mark-paid   – confirm pending deposit
 *   POST   /admin/api/payments/{id}/refund       – create refund record
 */
class PaymentTransactionController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/payments
     *
     * Optional filters: status, type, gateway, customer_id, appointment_id,
     *   from (Y-m-d), to (Y-m-d)
     * Optional sort: sort=created_at|amount  dir=asc|desc
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::with([
            'appointment:id,starts_at,status',
            'customer:id,first_name,last_name,email',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('gateway')) {
            $query->where('gateway', $request->input('gateway'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        if ($request->filled('appointment_id')) {
            $query->where('appointment_id', $request->integer('appointment_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $sort    = in_array($request->input('sort'), ['created_at', 'amount']) ? $request->input('sort') : 'created_at';
        $dir     = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 20), 100);

        $transactions = $query->orderBy($sort, $dir)->paginate($perPage);

        return response()->json($transactions);
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/payments/summary
     *
     * Returns aggregate totals: total_paid (cents), by_gateway[], by_type[],
     * pending_deposits, refunded_amount
     */
    public function summary(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $base = PaymentTransaction::query();

        if ($from) {
            $base->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $base->whereDate('created_at', '<=', $to);
        }

        $totalPaid = (clone $base)->where('status', 'paid')->sum('amount');

        $byGateway = (clone $base)
            ->selectRaw('gateway, status, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('gateway', 'status')
            ->get();

        $byType = (clone $base)
            ->selectRaw('type, status, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('type', 'status')
            ->get();

        $pendingDeposits = (clone $base)
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->count();

        $refundedAmount = (clone $base)
            ->where('type', 'refund')
            ->where('status', 'paid')
            ->sum('amount');

        return response()->json([
            'total_paid_cents'  => $totalPaid,
            'total_paid'        => round($totalPaid / 100, 2),
            'pending_deposits'  => $pendingDeposits,
            'refunded_cents'    => $refundedAmount,
            'refunded'          => round($refundedAmount / 100, 2),
            'by_gateway'        => $byGateway,
            'by_type'           => $byType,
        ]);
    }

    // ── Single ────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/payments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $transaction = PaymentTransaction::with([
            'appointment',
            'customer:id,first_name,last_name,email,phone',
            'refunds',
            'refundOf',
        ])->findOrFail($id);

        return response()->json($transaction);
    }

    // ── Mark Paid ─────────────────────────────────────────────────────────────

    /**
     * PATCH /admin/api/payments/{id}/mark-paid
     *
     * Marks a pending deposit as received (e.g. cash payment).
     */
    public function markPaid(int $id): JsonResponse
    {
        $transaction = PaymentTransaction::findOrFail($id);

        if ($transaction->status === 'paid') {
            return response()->json(['message' => 'Transaction already marked as paid'], 422);
        }

        $transaction->update([
            'status'       => 'paid',
            'processed_at' => now(),
        ]);

        return response()->json(['message' => 'Transaction marked as paid', 'data' => $transaction]);
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    /**
     * POST /admin/api/payments/{id}/refund
     *
     * Creates a refund record linked to the original transaction.
     * Body: { amount?: int (cents), reason?: string }
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $original = PaymentTransaction::findOrFail($id);

        if ($original->status !== 'paid') {
            return response()->json(['message' => 'Only paid transactions can be refunded'], 422);
        }

        $validated = $request->validate([
            'amount' => 'nullable|integer|min:1|max:' . $original->amount,
            'reason' => 'nullable|string|max:500',
        ]);

        $refundAmount = $validated['amount'] ?? $original->amount;

        $refund = DB::transaction(function () use ($original, $refundAmount, $validated) {
            // Track refunded amount on original
            $original->increment('refunded_amount', $refundAmount);

            // Create refund transaction
            return PaymentTransaction::create([
                'appointment_id' => $original->appointment_id,
                'customer_id'    => $original->customer_id,
                'gateway'        => $original->gateway,
                'type'           => 'refund',
                'status'         => 'paid',
                'amount'         => $refundAmount,
                'currency'       => $original->currency,
                'refund_of'      => $original->id,
                'refund_reason'  => $validated['reason'] ?? null,
                'processed_at'   => now(),
            ]);
        });

        return response()->json([
            'message' => 'Refund recorded',
            'data'    => $refund,
        ], 201);
    }
}
