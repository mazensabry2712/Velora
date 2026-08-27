<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Queue;

use App\Application\Queue\Actions\SetQueuePriority;
use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Queue;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class SetQueuePriorityTest extends TestCase
{
    public function test_waiting_queue_can_change_priority(): void
    {
        $queue = new Queue(['id' => 10, 'status' => 'waiting', 'is_vip' => false]);

        /** @var QueueRepository&MockInterface $repository */
        $repository = Mockery::mock(QueueRepository::class);
        $repository->expects('findById')->with(10)->once()->andReturn($queue);
        $repository->expects('update')->with($queue, ['is_vip' => true])->once()->andReturnTrue();

        $result = (new SetQueuePriority($repository))->execute(10, true);

        $this->assertTrue($result->is_vip);
    }

    public function test_non_waiting_queue_is_rejected(): void
    {
        $queue = new Queue(['id' => 10, 'status' => 'serving', 'is_vip' => false]);

        /** @var QueueRepository&MockInterface $repository */
        $repository = Mockery::mock(QueueRepository::class);
        $repository->expects('findById')->with(10)->once()->andReturn($queue);
        $repository->shouldNotReceive('update');

        $this->expectException(ValidationException::class);

        (new SetQueuePriority($repository))->execute(10, true);
    }
}
