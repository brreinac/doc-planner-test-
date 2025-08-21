<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\SlotsParser;
use App\Model\Slot;
use App\Repository\SlotRepositoryInterface;

final class DummySlotRepo implements SlotRepositoryInterface
{
    public ?Slot $returned = null;
    public array $saved = [];

    public function findByDoctorAndStart(int $doctorId, \DateTimeImmutable $start): ?Slot
    {
        return $this->returned;
    }

    public function save(Slot $slot): void
    {
        $this->saved[] = $slot;
    }
}

final class SlotsParserTest extends TestCase
{
    public function testCreatesNewSlotWhenNoneExists(): void
    {
        $repo = new DummySlotRepo();
        $parser = new SlotsParser($repo);

        $slots = [['start' => '2025-08-20T10:00:00+00:00', 'end' => '2025-08-20T10:30:00+00:00']];

        $result = iterator_to_array($parser->parseSlots($slots, 1));
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Slot::class, $result[0]);
        $this->assertEquals(new \DateTimeImmutable('2025-08-20T10:00:00+00:00'), $result[0]->getStart());
    }

    public function testUpdatesExistingSlotEndIfDifferent(): void
    {
        $existing = new Slot(1, new \DateTimeImmutable('2025-08-20T10:00:00+00:00'), new \DateTimeImmutable('2025-08-20T10:30:00+00:00'));
        $repo = new DummySlotRepo();
        $repo->returned = $existing;

        $parser = new SlotsParser($repo);

        $slots = [['start' => '2025-08-20T10:00:00+00:00', 'end' => '2025-08-20T10:45:00+00:00']];
        $result = iterator_to_array($parser->parseSlots($slots, 1));
        $this->assertCount(1, $result);
        $this->assertSame($existing, $result[0]);
        $this->assertEquals(new \DateTimeImmutable('2025-08-20T10:45:00+00:00'), $existing->getEnd());
    }
}
