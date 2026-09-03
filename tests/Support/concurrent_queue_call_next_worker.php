<?php

declare(strict_types=1);

use App\Application\Queue\Actions\CallNextQueueEntry;
use App\Models\Tenant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$root = dirname(__DIR__, 2);
$base = $root . '/storage/framework/testing/concurrency';
@mkdir($base, 0777, true);
$options = getopt('', ['tenant:', 'date:', 'token:']);
$token = (string) ($options['token'] ?? '');
$pid = (string) getmypid();
$ready = "$base/$token.ready.$pid";
$result = "$base/$token.result.$pid";
$state = "$base/$token.state.$pid";
$go = "$base/$token.go";

try {
    file_put_contents($state, json_encode(['phase' => 'starting', 'pid' => getmypid()], JSON_THROW_ON_ERROR));
    require $root . '/tests/bootstrap.php';
    /** @var Application $app */
    $app = require $root . '/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $tenant = Tenant::on((string) config('tenancy.database.central_connection', config('database.default')))
        ->findOrFail((string) $options['tenant']);
    tenancy()->initialize($tenant);
    file_put_contents($state, json_encode(['phase' => 'tenant_ready'], JSON_THROW_ON_ERROR));
    file_put_contents($ready, 'ready');

    $deadline = microtime(true) + 30;
    while (! file_exists($go)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('CONCURRENCY_START_TIMEOUT');
        }
        usleep(10_000);
    }

    file_put_contents($state, json_encode(['phase' => 'call_next'], JSON_THROW_ON_ERROR));
    $queue = app(CallNextQueueEntry::class)->execute();
    file_put_contents($result, json_encode([
        'status' => 'success',
        'queue_id' => $queue?->id,
        'queue_status' => $queue?->status,
    ], JSON_THROW_ON_ERROR));
    exit(0);
} catch (Throwable $e) {
    file_put_contents($state, json_encode(['phase' => 'failure', 'class' => $e::class, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR));
    file_put_contents($result, json_encode(['status' => 'failure', 'class' => $e::class, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR));
    exit(1);
} finally {
    @unlink($ready);
}
