<?php

namespace App\Tests\Sync\Writer;

use App\Sync\Writer\VolunteerSyncSnapshotWriter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Query;
use PHPUnit\Framework\TestCase;

class VolunteerSyncSnapshotWriterTest extends TestCase
{
    private function makeDeadlock() : DeadlockException
    {
        $driverException = $this->createMock(DriverException::class);

        return new DeadlockException($driverException, new Query('', [], []));
    }

    public function testFlushSortsBufferByExternalIdBeforeInsert()
    {
        $captured = null;

        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
             ->method('executeStatement')
             ->willReturnCallback(function (string $sql, array $params) use (&$captured) {
                 $captured = $params;

                 return 1;
             });

        $writer = new VolunteerSyncSnapshotWriter($conn);

        $at = new \DateTimeImmutable('2026-06-14 20:40:00');
        $writer->queue('ZULU', $at, ['k' => 1]);
        $writer->queue('ALPHA', $at, ['k' => 2]);
        $writer->queue('MIKE', $at, ['k' => 3]);

        $writer->flush();

        // 3 params per row; first param of each row is the external_id
        $this->assertSame(['ALPHA', 'MIKE', 'ZULU'], [$captured[0], $captured[3], $captured[6]],
            'flush() must sort the buffer by external_id so concurrent chunks acquire next-key locks in the same order'
        );
    }

    public function testFlushRetriesOnDeadlockAndEventuallySucceeds()
    {
        $conn  = $this->createMock(Connection::class);
        $calls = 0;
        $conn->expects($this->exactly(2))
             ->method('executeStatement')
             ->willReturnCallback(function () use (&$calls) {
                 $calls++;
                 if ($calls < 2) {
                     throw $this->makeDeadlock();
                 }

                 return 1;
             });

        $writer = new VolunteerSyncSnapshotWriter($conn);
        $writer->queue('A', new \DateTimeImmutable(), ['k' => 1]);

        $writer->flush();

        $this->assertSame(0, $writer->bufferedCount(), 'buffer must be cleared after a successful retry');
    }

    public function testFlushGivesUpAfterMaxDeadlockRetries()
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->exactly(3))
             ->method('executeStatement')
             ->willThrowException($this->makeDeadlock());

        $writer = new VolunteerSyncSnapshotWriter($conn);
        $writer->queue('A', new \DateTimeImmutable(), ['k' => 1]);

        $this->expectException(DeadlockException::class);
        $writer->flush();
    }

    public function testNonDeadlockErrorBubblesImmediatelyWithoutRetry()
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
             ->method('executeStatement')
             ->willThrowException(new \RuntimeException('something else'));

        $writer = new VolunteerSyncSnapshotWriter($conn);
        $writer->queue('A', new \DateTimeImmutable(), ['k' => 1]);

        $this->expectException(\RuntimeException::class);
        $writer->flush();
    }
}
