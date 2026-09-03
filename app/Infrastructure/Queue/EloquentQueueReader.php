<?php

namespace App\Infrastructure\Queue;

use App\Domain\Queue\Contracts\QueueReader;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class EloquentQueueReader implements QueueReader
{
    public function forDate(string $date, ?string $status = null): Collection
    {
        return Queue::query()
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['appointment.customer', 'appointment.staff'])
            ->orderByDesc('is_vip')
            ->orderBy('queue_number')
            ->get()
            ->each(function (Queue $queue) use ($date): void {
                $queue->estimated_wait_time = $this->estimatedWaitTime($queue, $date);
            });
    }

    public function forCustomer(User $user, string $date): array
    {
        $customer = $user->customerProfile;

        $queue = Queue::query()
            ->whereHas('appointment', function ($query) use ($customer): void {
                $query->where('customer_id_new', $customer?->id);
            })
            ->where('status', 'waiting')
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->with('appointment')
            ->first();

        if (! $queue) {
            return [
                'queue' => null,
                'position' => 0,
                'estimated_wait_time' => 0,
                'is_vip' => false,
            ];
        }

        $position = Queue::query()
            ->where('status', 'waiting')
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->where(function ($query) use ($queue): void {
                $query->where('is_vip', '>', $queue->is_vip)
                    ->orWhere(function ($q) use ($queue): void {
                        $q->where('is_vip', $queue->is_vip)
                            ->where('queue_number', '<', $queue->queue_number);
                    });
            })
            ->count() + 1;

        $queue->position = $position;
        $queue->estimated_wait_time = $this->estimatedWaitTime($queue, $date);

        return [
            'queue' => $queue,
            'position' => $position,
            'estimated_wait_time' => (int) $queue->estimated_wait_time,
            'is_vip' => (bool) $queue->is_vip,
        ];
    }

    public function status(string $queueNumber, string $date): array
    {
        $queue = Queue::query()
            ->where('queue_number', $queueNumber)
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->with('appointment.service', 'appointment.staff')
            ->first();

        if (! $queue) {
            return ['queue' => null];
        }

        $peopleAhead = Queue::query()
            ->where('status', 'waiting')
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->where(function ($query) use ($queue): void {
                $query->where('is_vip', '>', $queue->is_vip)
                    ->orWhere(function ($q) use ($queue): void {
                        $q->where('is_vip', $queue->is_vip)
                            ->where('queue_number', '<', $queue->queue_number);
                    });
            })
            ->count();

        $currentlyServing = Queue::query()
            ->where('status', 'serving')
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->first();

        return [
            'queue' => $queue,
            'people_ahead' => $peopleAhead,
            'estimated_wait_time' => $this->estimatedWaitTime($queue, $date),
            'currently_serving' => $currentlyServing?->queue_number,
        ];
    }

    private function estimatedWaitTime(Queue $queue, string $date): int
    {
        $queuesAhead = Queue::query()
            ->where('status', 'waiting')
            ->where(function ($query) use ($date): void {
                $query->whereDate('queue_date', $date)
                    ->orWhere(function ($legacy) use ($date): void {
                        $legacy->whereNull('queue_date')->whereDate('created_at', $date);
                    });
            })
            ->where(function ($query) use ($queue): void {
                $query->where('is_vip', '>', $queue->is_vip)
                    ->orWhere(function ($q) use ($queue): void {
                        $q->where('is_vip', $queue->is_vip)
                            ->where('queue_number', '<', $queue->queue_number);
                    });
            })
            ->count();

        return $queuesAhead * 15;
    }
}
