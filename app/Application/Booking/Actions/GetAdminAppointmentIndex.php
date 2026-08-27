<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class GetAdminAppointmentIndex
{
    public function __construct(private readonly AppointmentRepositoryInterface $appointments) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{paginatedData: LengthAwarePaginator, appointmentsByDate: Collection, stats: array<string,mixed>, services: Collection, staffMembers: Collection}
     */
    public function execute(array $filters, int $perPage): array
    {
        $paginatedData = $this->appointments->paginate($filters, $perPage);
        $all = $this->appointments->forAdminIndex($filters);

        $appointmentsByDate = $all
            ->groupBy(fn (Appointment $appointment) => $appointment->date->format('Y-m-d'))
            ->map(function (Collection $day, string $date): array {
                $total = $day->count();
                $dateCarbon = \Carbon\Carbon::parse($date);
                $confirmed = $day->where('status', 'confirmed')->count();
                $pending = $day->where('status', 'pending')->count();
                $completed = $day->where('status', 'completed')->count();
                $cancelled = $day->where('status', 'cancelled')->count();

                return [
                    'date' => $date,
                    'date_formatted' => $dateCarbon->translatedFormat('l, F j, Y'),
                    'is_today' => $dateCarbon->isToday(),
                    'is_past' => $dateCarbon->isPast() && ! $dateCarbon->isToday(),
                    'diff_humans' => $dateCarbon->diffForHumans(),
                    'appointments' => $day->sortBy('time_slot')->values(),
                    'appointment_ids' => $day->pluck('id')->values()->all(),
                    'total' => $total,
                    'confirmed' => $confirmed,
                    'pending' => $pending,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'confirmed_percent' => $total > 0 ? round(($confirmed / $total) * 100) : 0,
                    'pending_percent' => $total > 0 ? round(($pending / $total) * 100) : 0,
                    'completed_percent' => $total > 0 ? round(($completed / $total) * 100) : 0,
                    'cancelled_percent' => $total > 0 ? round(($cancelled / $total) * 100) : 0,
                    'progress_percent' => $total > 0 ? round(($day->whereIn('status', ['completed', 'cancelled'])->count() / $total) * 100) : 0,
                    'is_tomorrow' => $dateCarbon->isTomorrow(),
                    'revenue' => $day->sum(fn (Appointment $appointment) => $appointment->service?->price ?? 0),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $stats = $this->appointments->getTodayStats();
        $stats['this_week'] = $this->appointments->getWeeklyStats()['this_week'];

        $past = Appointment::query()->where('date', '<', today()->format('Y-m-d'))->count();
        $completedPast = Appointment::query()->where('date', '<', today()->format('Y-m-d'))->where('status', 'completed')->count();
        $stats['no_show_rate'] = $past > 0 ? round((($past - $completedPast) / $past) * 100, 1) : 0;
        $monthCount = Appointment::query()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count();
        $stats['cancelled_month'] = Appointment::query()->where('status', 'cancelled')->whereMonth('date', now()->month)->count();
        $stats['avg_daily'] = now()->day > 0 ? round($monthCount / now()->day, 1) : 0;

        $staffMembers = User::role(['Staff', 'Admin Tenant'])->get();
        $services = Service::where('is_active', true)->get();

        return compact('paginatedData', 'appointmentsByDate', 'stats', 'services', 'staffMembers');
    }
}
