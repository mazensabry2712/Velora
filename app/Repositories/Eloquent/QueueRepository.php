<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domain\Queue\Contracts\QueueRepository as QueueRepositoryContract;
use App\Models\Queue;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class QueueRepository implements QueueRepositoryInterface, QueueRepositoryContract
{
    public function findById(int $id): ?Queue
    {
        return Queue::find($id);
    }

    public function getByDate(string $date): Collection
    {
        return Queue::with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->orderByDesc('is_vip')
            ->orderBy('id')
            ->get();
    }

    public function getActive(): Collection
    {
        return Queue::with(['appointment.customer'])
            ->whereIn('status', ['waiting', 'serving'])
            ->orderByDesc('is_vip')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): Queue
    {
        return Queue::create($data);
    }

    public function update(Queue $queue, array $data): bool
    {
        return $queue->update($data);
    }

    public function delete(Queue $queue): bool
    {
        return (bool) $queue->delete();
    }

    public function callNext(?string $date = null): ?Queue
    {
        $date = $date ?? Carbon::today()->toDateString();

        return DB::transaction(function () use ($date): ?Queue {
            $next = Queue::where('status', 'waiting')
                ->where(function ($query) use ($date): void {
                    $query->whereDate('queue_date', $date)
                        ->orWhere(function ($legacy) use ($date): void {
                            $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                        });
                })
                ->orderByDesc('is_vip')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                return null;
            }

            $next->update(['status' => 'serving']);

            return $next->refresh();
        });
    }

    public function getDailyStats(string $date): array
    {
        $rows = Queue::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting'   THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'serving'   THEN 1 ELSE 0 END) as serving,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN is_vip = 1           THEN 1 ELSE 0 END) as vip
            ")
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->first();

        return $rows ? $rows->toArray() : [
            'total' => 0, 'waiting' => 0, 'serving' => 0, 'completed' => 0, 'vip' => 0,
        ];
    }

    public function getOverallStats(): array
    {
        return [
            'waiting'   => Queue::where('status', 'waiting')->count(),
            'serving'   => Queue::where('status', 'serving')->count(),
            'completed' => Queue::where('status', 'completed')->count(),
        ];
    }

    public function moveToNextDay(string $date, string $status): int
    {
        $nextDay = Carbon::parse($date)->addDay()->toDateString();

        $queues = Queue::where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->where('status', $status)
            ->get();

        foreach ($queues as $queue) {
            $queue->queue_date = $nextDay;
            $queue->updated_at = now();
            $queue->save();
        }

        return $queues->count();
    }
}
