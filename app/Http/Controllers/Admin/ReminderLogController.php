<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderLogController extends Controller
{
    /**
     * GET /admin/api/reminder-logs
     *
     * Query params:
     *   rule_id        int     filter by reminder rule
     *   appointment_id int     filter by appointment
     *   channel        string  email|sms|push
     *   status         string  sent|failed|pending
     *   date_from      date    scheduled_at >=
     *   date_to        date    scheduled_at <=
     *   per_page       int     default 25
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReminderLog::with(['rule:id,name,trigger_type,channel', 'appointment:id,starts_at,status'])
            ->orderByDesc('scheduled_at');

        if ($request->filled('rule_id')) {
            $query->where('rule_id', $request->integer('rule_id'));
        }

        if ($request->filled('appointment_id')) {
            $query->where('appointment_id', $request->integer('appointment_id'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /admin/api/reminder-logs/{id}
     * Single log entry with full details.
     */
    public function show(int $id): JsonResponse
    {
        $log = ReminderLog::with(['rule', 'appointment'])->findOrFail($id);

        return response()->json($log);
    }

    /**
     * GET /admin/api/reminder-logs/stats
     * Aggregated stats for the last 30 days (or supplied range).
     */
    public function stats(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $base = ReminderLog::whereDate('scheduled_at', '>=', $from)
                            ->whereDate('scheduled_at', '<=', $to);

        $total  = (clone $base)->count();
        $sent   = (clone $base)->where('status', 'sent')->count();
        $failed = (clone $base)->where('status', 'failed')->count();

        $byChannel = (clone $base)
            ->selectRaw('channel, count(*) as total, sum(status = "sent") as sent_count, sum(status = "failed") as failed_count')
            ->groupBy('channel')
            ->get();

        $byRule = (clone $base)
            ->selectRaw('rule_id, count(*) as total, sum(status = "sent") as sent_count')
            ->with('rule:id,name')
            ->groupBy('rule_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'period'         => ['from' => $from, 'to' => $to],
            'total'          => $total,
            'sent'           => $sent,
            'failed'         => $failed,
            'delivery_rate'  => $total > 0 ? round($sent / $total * 100, 1) : 0,
            'by_channel'     => $byChannel,
            'top_rules'      => $byRule,
        ]);
    }
}
