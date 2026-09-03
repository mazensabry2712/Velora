<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Booking\Exceptions\SlotUnavailableException;
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
final class DuplicateBookingConcurrencyScenarioTest extends TenantTestCase
{
    #[Test]
    public function duplicate_concurrent_submission_creates_only_one_appointment(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('CONC-002 requires independent PHP worker processes; execute the race gate in the MySQL CI/Linux environment.');
        }

        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDays(6)->startOfDay();
        $time = '10:00';
        $slotStartsAt = $date->copy()->setTimeFromTimeString($time);
        $email = 'duplicate-concurrent-' . uniqid('', false) . '@example.com';

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

        $token = 'duplicate-' . bin2hex(random_bytes(8));
        $base = storage_path('framework/testing/concurrency');
        @mkdir($base, 0777, true);
        $go = $base . '/' . $token . '.go';
        $worker = base_path('tests/Support/concurrent_booking_worker.php');
        $processes = [];

        // Child processes use independent DB connections, so every fixture must be committed first.
        if (DB::connection()->transactionLevel() > 0) {
            DB::connection()->commit();
        }
        $central = DB::connection((string) config('tenancy.database.central_connection', config('database.default')));
        if ($central->transactionLevel() > 0) {
            $central->commit();
        }

        try {
            foreach ([1, 2] as $_) {
                $process = new Process([
                    PHP_BINARY,
                    '-d',
                    'memory_limit=512M',
                    $worker,
                    '--tenant=' . $this->tenant->id,
                    '--staff-user=' . $this->staffMember->id,
                    '--service=' . $this->service->id,
                    '--date=' . $date->toDateString(),
                    '--time=' . $time,
                    '--email=' . $email,
                    '--token=' . $token,
                ], base_path(), null, null, 45);
                $process->setTimeout(45);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 30;
            while (count(glob($base . '/' . $token . '.ready.*')) < 2) {
                if (microtime(true) > $deadline) {
                    $this->fail('CONC-002 workers did not reach the synchronized booking barrier.');
                }
                usleep(10_000);
            }

            file_put_contents($go, 'go');
            $results = [];

            foreach ($processes as $process) {
                $process->wait();
                $decoded = null;
                foreach (glob($base . '/' . $token . '.result.*') as $resultFile) {
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
                    'message' => $decoded['message'] ?? trim($process->getErrorOutput()) ?: trim($process->getOutput()),
                ];
            }

            $successes = array_values(array_filter($results, fn (array $result): bool => $result['status'] === 'success'));
            $failures = array_values(array_filter($results, fn (array $result): bool => $result['status'] === 'failure'));

            $this->assertCount(1, $successes, 'Expected one duplicate submission to succeed. Results: ' . json_encode($results));
            $this->assertCount(1, $failures, 'Expected one duplicate submission to be rejected. Results: ' . json_encode($results));
            $this->assertSame(SlotUnavailableException::class, $failures[0]['class'] ?? null);

            $this->assertSame(
                1,
                Appointment::query()
                    ->where('staff_id_new', $this->staff->id)
                    ->where('starts_at', $slotStartsAt->copy()->utc())
                    ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                    ->count(),
            );

            $this->assertSame(1, Customer::query()->where('email', $email)->count());
        } finally {
            @unlink($go);
            foreach (glob($base . '/' . $token . '.*') as $file) {
                @unlink($file);
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

            Customer::query()->where('email', $email)->delete();
        }
    }
}
