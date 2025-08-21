<?php
declare(strict_types=1);

namespace Tests\Unit\Synchronizer;

use PHPUnit\Framework\TestCase;
use App\Synchronizer\StaticDoctorSlotsSynchronizer;
use App\Provider\Static\StaticDataProviderInterface;
use App\Normalizer\NameNormalizer;
use App\Service\SlotsParser;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\SlotRepositoryInterface;
use App\Policy\ErrorReportingPolicy;
use App\Policy\ClockInterface;
use App\Model\Doctor;
use App\Model\Slot;

/** Small fake clock */
final class FixedClock implements ClockInterface
{
    private \DateTimeImmutable $dt;
    public function __construct(string $date) { $this->dt = new \DateTimeImmutable($date); }
    public function now(): \DateTimeImmutable { return $this->dt; }
}

final class StaticDoctorSlotsSynchronizerTest extends TestCase
{
    public function testSynchronizePersistsDoctorsAndSlotsAndHandlesSlotError(): void
    {
        $data = [
            ['id' => 1, 'name' => 'john doe', 'slots' => [
                ['start' => '2025-08-20T10:00:00+00:00', 'end' => '2025-08-20T10:30:00+00:00']
            ]],
            ['id' => 2, 'name' => 'bad slots', 'slots' => 'not an array'] // malformed slots triggers parse skip or error
        ];

        // provider stub
        $provider = $this->createMock(StaticDataProviderInterface::class);
        $provider->method('getData')->willReturn($data);

        $normalizer = new NameNormalizer();

        // SlotsParser uses SlotRepositoryInterface to find existing slots
        $slotRepoForParser = $this->createMock(App\Repository\SlotRepositoryInterface::class);
        $slotRepoForParser->method('findByDoctorAndStart')->willReturn(null);
        $slotsParser = new SlotsParser($slotRepoForParser);

        // Doctor repository: capture saves
        $saved = [];
        $doctorRepo = $this->createMock(DoctorRepositoryInterface::class);
        $doctorRepo->method('find')->willReturn(null);
        $doctorRepo->expects($this->atLeastOnce())->method('save')->willReturnCallback(function($d) use (&$saved) { $saved[] = $d; });

        // Slot repository for actual persist: expect save called once for doctor 1
        $slotRepo = $this->createMock(SlotRepositoryInterface::class);
        $slotRepo->expects($this->once())->method('save')->with($this->isInstanceOf(Slot::class));

        $clock = new FixedClock('2025-08-18'); // not Sunday -> reporting allowed
        $errorPolicy = new ErrorReportingPolicy($clock);

        $sync = new StaticDoctorSlotsSynchronizer(
            $provider,
            $normalizer,
            $slotsParser,
            $doctorRepo,
            $slotRepo,
            $errorPolicy
        );

        $sync->synchronize();

        // After run, doctor 1 and doctor 2 should have been saved at least once;
        // doctor 2 may have been saved twice (initial + markError). We assert the last saved is for id 2 and hasError = true.
        $this->assertNotEmpty($saved);
        $ids = array_map(fn($d) => $d->getId(), $saved);
        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);

        // find last saved doctor with id 2
        $last2 = null;
        foreach (array_reverse($saved) as $s) {
            if ($s->getId() === 2) { $last2 = $s; break; }
        }
        $this->assertNotNull($last2);
        $this->assertTrue($last2->hasError());
    }
}
