<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingConcurrencyScenarioTest extends TenantTestCase
{
    #[Test]
    public function two_customers_booking_the_same_slot_result_in_exactly_one_persisted_booking(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDays(5)->startOfDay();
        $time = '10:00';
        $slotStartsAt = $date->copy()->setTimeFromTimeString($time);

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        StaffWorkingHours::updateOrCreate(
            ['staff_id' => $this->staff->id, 'day_of_week' => $date->dayOfWeek],
            ['start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true],
        );

        $emails = [
            'concurrent-a-' . uniqid('', false) . '@example.com',
            'concurrent-b-' . uniqid('', false) . '@example.com',
        ];
        $token = 'booking-' . bin2hex(random_bytes(8));
        $base = storage_path('framework/testing/concurrency');
        @mkdir($base, 0777, true);
        $go = $base . '/' . $token . '.go';
        $worker = base_path('tests/Support/concurrent_booking_worker.php');
        $processes = [];
        $results = [];

        if (DB::connection()->transactionLevel() > 0) {
            DB::connection()->commit();
        }
        $central = DB::connection((string) config('tenancy.database.central_connection', config('database.default')));
        if ($central->transactionLevel() > 0) {
            $central->commit();
        }

        try {
            foreach ($emails as $email) {
                $process = new Process([
                    PHP_BINARY,
                    $worker,
                    '--tenant=' . $this->tenant->id,
                    '--staff-user=' . $this->staffMember->id,
                    '--service=' . $this->service->id,
                    '--date=' . $date->toDateString(),
                    '--time=' . $time,
                    '--email=' . $email,
                    '--token=' . $token,
                ], base_path());
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }

            $readyDeadline = microtime(true) + 15;
            while (count(glob($base . '/' . $token . '.ready.*')) < 2) {
                if (microtime(true) > $readyDeadline) {
                    $diagnostics = [];
                    foreach ($processes as $index => $process) {
                        if ($process->isRunning()) {
                            $process->stop(1);
                        }
                        $diagnostics[] = [
                            'worker' => $index + 1,
                            'exit_code' => $process->getExitCode(),
                            'stdout' => trim($process->getOutput()),
                            'stderr' => trim($process->getErrorOutput()),
                        ];
                    }

                    $this->fail(
                        'Concurrent booking workers did not both reach the synchronized start barrier. Diagnostics: ' .
                        json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    );
                }
                usleep(10_000);
            }

            file_put_contents($go, 'go');

            foreach ($processes as $process) {
                $process->wait();
                $output = trim($process->getOutput());
                $errors = trim($process->getErrorOutput());
                $resultFiles = glob($base . '/' . $token . '.result.*');
                $decoded = null;

                foreach ($resultFiles as $resultFile) {
                    $candidate = json_decode((string) file_get_contents($resultFile), true);
                    if (is_array($candidate) && !isset($candidate['_claimed'])) {
                        $candidate['_claimed'] = true;
                        file_put_contents($resultFile, json_encode($candidate));
                        $decoded = $candidate;
                        break;
                    }
                }

                $results[] = [
                    'exit_code' => $process->getExitCode(),
                    'status' => $decoded['status'] ?? null,
                    'class' => $decoded['class'] ?? null,
                    'message' => $decoded['message'] ?? $errors ?: $output,
                    'appointment_id' => $decoded['appointment_id'] ?? null,
                ];
            }

            $successes = array_values(array_filter($results, fn (array $result): bool => $result['status'] === 'success'));
            $failures = array_values(array_filter($results, fn (array $result): bool => $result['status'] === 'failure'));

            $this->assertCount(1, $successes, 'Expected exactly one concurrent booking worker to succeed. Results: ' . json_encode($results));
            $this->assertCount(1, $failures, 'Expected exactly one concurrent booking worker to be rejected. Results: ' . json_encode($results));
            $this->assertSame(
                \App\Domain\Booking\Exceptions\SlotUnavailableException::class,
                $failures[0]['class'] ?? null,
                'The losing booking must fail through the domain slot-unavailable path. Results: ' . json_encode($results),
            );

            $count = Appointment::query()
                ->where('staff_id_new', $this->staff->id)
                ->where('starts_at', $slotStartsAt->copy()->utc())
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->count();

            $this->assertSame(1, $count, 'Concurrent requests must leave exactly one active appointment for the slot.');
        } finally {
            @unlink($go);
            foreach (glob($base . '/' . $token . '.*') as $file) {
                @unlink($file);
            }

            foreach ($emails as $email) {
                Customer::query()->where('email', $email)->delete();
            }

            $appointmentIds = Appointment::query()
                ->where('staff_id_new', $this->staff->id)
                ->where('starts_at', $slotStartsAt->copy()->utc())
                ->pluck('id');

            if ($appointmentIds->isNotEmpty()) {
                DB::table('queues')->whereIn('appointment_id', $appointmentIds)->delete();
                DB::table('appointment_status_histories')->whereIn('appointment_id', $appointmentIds)->delete();
                Appointment::whereIn('id', $appointmentIds)->delete();
            }
        }
    }
}
