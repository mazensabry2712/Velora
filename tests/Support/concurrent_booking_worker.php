<?php

declare(strict_types=1);

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Models\Tenant;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/** @var Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', [
    'tenant:',
    'staff-user:',
    'service:',
    'date:',
    'time:',
    'email:',
    'token:',
]);

$token = (string) ($options['token'] ?? '');
$base = storage_path('framework/testing/concurrency');
@mkdir($base, 0777, true);
$ready = $base . '/' . $token . '.ready.' . getmypid();
$go = $base . '/' . $token . '.go';
$result = $base . '/' . $token . '.result.' . getmypid();

try {
    $tenant = Tenant::on((string) config('tenancy.database.central_connection', config('database.default')))
        ->findOrFail((string) $options['tenant']);
    tenancy()->initialize($tenant);

    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 15;
    while (! file_exists($go)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('CONCURRENCY_START_TIMEOUT');
        }
        usleep(10_000);
    }

    $booking = app(CreatePublicBooking::class)->execute(new PublicBookingData(
        customerName: 'Concurrent Booking ' . getmypid(),
        customerEmail: (string) $options['email'],
        customerPhone: '+201000000' . str_pad((string) (getmypid() % 10000), 4, '0', STR_PAD_LEFT),
        serviceId: (int) $options['service'],
        staffUserId: (int) $options['staff-user'],
        resourceId: null,
        appointmentDate: (string) $options['date'],
        appointmentTime: (string) $options['time'],
        requestedTimezone: config('app.timezone'),
        notes: null,
    ));

    file_put_contents($result, json_encode([
        'status' => 'success',
        'appointment_id' => $booking['appointment']->id,
    ], JSON_THROW_ON_ERROR));
    exit(0);
} catch (Throwable $e) {
    file_put_contents($result, json_encode([
        'status' => 'failure',
        'class' => $e::class,
        'message' => $e->getMessage(),
    ], JSON_THROW_ON_ERROR));
    exit(1);
} finally {
    @unlink($ready);
}
