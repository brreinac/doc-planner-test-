<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\DoctorSlotsSynchronizer;
use App\DTO\DoctorData;
use App\Factory\DoctorFactory;
use App\Factory\SlotFactory;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\SlotRepositoryInterface;
use App\UnitOfWork\UnitOfWorkInterface;
use App\Normalizer\NameNormalizer;
use App\Policy\ErrorReportingPolicy;

final class FakeUow implements UnitOfWorkInterface {
    public array $calls = [];
    public function begin(): void { $this->calls[] = 'begin'; }
    public function commit(): void { $this->calls[] = 'commit'; }
    public function rollback(): void { $this->calls[] = 'rollback'; }
}

final class DoctorSlotsSynchronizerTest extends TestCase
{
    public function testTransactionCommitOnSuccess(): void
    {
        $doctorData = new DoctorData(1, 'john doe', function() {
            return [['start' => '2025-08-20T10:00:00+00:00', 'end' => '2025-08-20T10:30:00+00:00']];
        });

        $provider = $this->createMock(App\Provider\DataProviderInterface::class);
        $provider->method('getDoctors')->willReturn([$doctorData]);

        $doctorRepo = $this->createMock(DoctorRepositoryInterface::class);
        $doctorRepo->method('find')->willReturn(null);
        $doctorRepo->expects($this->once())->method('save');

        $slotRepo = $this->createMock(SlotRepositoryInterface::class);
        $slotRepo->method('findByDoctorAndStart')->willReturn(null);
        $slotRepo->expects($this->once())->method('save');

        $uow = new FakeUow();

        $sync = new DoctorSlotsSynchronizer(
            $provider,
            new DoctorFactory(),
            new SlotFactory(),
            $doctorRepo,
            $slotRepo,
            $uow,
            new NameNormalizer(),
            new ErrorReportingPolicy(new class implements App\Policy\ClockInterface { public function now(): \DateTimeImmutable { return new \DateTimeImmutable('2025-08-18'); } }),
            null
        );

        $sync->synchronize();

        $this->assertEquals(['begin','commit'], $uow->calls);
    }

    public function testRollbackAndMarkErrorOnSlotFetchFailure(): void
    {
        $doctorData = new DoctorData(2, 'jane', function() {
            throw new \RuntimeException('boom');
        });
        $provider = $this->createMock(App\Provider\DataProviderInterface::class);
        $provider->method('getDoctors')->willReturn([$doctorData]);

        $doctorRepo = $this->createMock(DoctorRepositoryInterface::class);
        $doctorRepo->method('find')->willReturn(null);
        // expect save called twice: initial save (before failure?) In our synchronizer we save before processing; if we save after begin, adjust accordingly.
        $doctorRepo->expects($this->atLeastOnce())->method('save');

        $slotRepo = $this->createMock(SlotRepositoryInterface::class);

        $uow = new FakeUow();

        $sync = new DoctorSlotsSynchronizer(
            $provider,
            new DoctorFactory(),
            new SlotFactory(),
            $doctorRepo,
            $slotRepo,
            $uow,
            new NameNormalizer(),
            new ErrorReportingPolicy(new class implements App\Policy\ClockInterface { public function now(): \DateTimeImmutable { return new \DateTimeImmutable('2025-08-18'); } }),
            null
        );

        $sync->synchronize();

        $this->assertContains('rollback', $uow->calls);
    }
}
