<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Appointment;
use App\Models\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TenantTestCase;
use Illuminate\Support\Facades\DB;

#[Group('qa')]
#[Group('master-scenario')]
final class QueueConcurrencyScenarioTest extends TenantTestCase
{
    #[Test]
    public function two_staff_call_next_requests_cannot_serve_the_same_queue_entry(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('CONC-003 requires independent PHP worker processes; execute the race gate in the MySQL CI/Linux environment.');
        }

        $date = today()->toDateString();
        $appointment = Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '09:00',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(60),
            'ends_at_with_buffer' => now()->addMinutes(60),
            'timezone' => $this->staff->timezone ?: config('app.timezone'),
            'price' => $this->service->price,
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'qa-concurrency',
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A901',
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $token = 'queue-' . bin2hex(random_bytes(8));
        $base = storage_path('framework/testing/concurrency');
        @mkdir($base, 0777, true);
        $go = $base . '/' . $token . '.go';
        $worker = base_path('tests/Support/concurrent_queue_call_next_worker.php');

        if (DB::connection()->transactionLevel() > 0) {
            DB::connection()->commit();
        }
        $central = DB::connection((string) config('tenancy.database.central_connection', config('database.default')));
        if ($central->transactionLevel() > 0) {
            $central->commit();
        }

        $processes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = new Process([
                    PHP_BINARY,
                    '-d', 'memory_limit=512M',
                    $worker,
                    '--tenant=' . $this->tenant->id,
                    '--date=' . $date,
                    '--token=' . $token,
                ], base_path(), null, null, 45);
                $process->setTimeout(45);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 30;
            while (count(glob($base . '/' . $token . '.ready.*')) < 2) {
                if (microtime(true) > $deadline) {
                    $diagnostics = [];
                    foreach ($processes as $process) {
                        if ($process->isRunning()) {
                            $process->stop(1);
                        }
                        $diagnostics[] = [
                            'exit_code' => $process->getExitCode(),
                            'stdout' => trim($process->getOutput()),
                            'stderr' => trim($process->getErrorOutput()),
                        ];
                    }
                    $this->fail('CONC-003 workers did not reach the barrier. Diagnostics: ' . json_encode($diagnostics));
                }
                usleep(10_000);
            }

            file_put_contents($go, 'go');
            foreach ($processes as $process) {
                $process->wait();
            }

            $resultFiles = glob($base . '/' . $token . '.result.*');
            $results = array_map(static fn (string $file): array => json_decode((string) file_get_contents($file), true), $resultFiles);

            $this->assertCount(2, $results);
            $servedIds = array_values(array_filter(array_map(static fn (array $result) => $result['queue_id'] ?? null, $results)));
            $nullResults = count(array_filter($results, static fn (array $result): bool => ($result['queue_id'] ?? null) === null));
            $this->assertCount(1, $servedIds, 'Exactly one concurrent caller may acquire the waiting entry. Results: ' . json_encode($results));
            $this->assertSame(1, $nullResults, 'The second concurrent caller must observe no remaining waiting entry. Results: ' . json_encode($results));
            $this->assertSame($queue->id, $servedIds[0]);
            $this->assertSame('serving', $queue->fresh()->status);
        } finally {
            @unlink($go);
            foreach (glob($base . '/' . $token . '.*') as $file) {
                @unlink($file);
            }
            DB::table('queues')->where('id', $queue->id)->delete();
            DB::table('appointment_status_histories')->where('appointment_id', $appointment->id)->delete();
            Appointment::whereKey($appointment->id)->delete();
        }
    }
}
