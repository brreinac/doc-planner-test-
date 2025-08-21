<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\SlotRepositoryInterface;
use App\Model\Slot;

class SlotsParser
{
    private SlotRepositoryInterface $slotRepository;

    public function __construct(SlotRepositoryInterface $slotRepository)
    {
        $this->slotRepository = $slotRepository;
    }

    /**
     * Parse an array of slot payloads and return Slot instances (new or updated).
     *
     * @param array $slotsData each item: ['start' => ISO8601, 'end' => ISO8601]
     * @return iterable<Slot>
     */
    public function parseSlots(array $slotsData, int $doctorId): iterable
    {
        foreach ($slotsData as $slotData) {
            if (!isset($slotData['start']) || !isset($slotData['end'])) {
                // skip malformed
                continue;
            }

            $start = new \DateTimeImmutable($slotData['start']);
            $end   = new \DateTimeImmutable($slotData['end']);

            $existing = $this->slotRepository->findByDoctorAndStart($doctorId, $start);

            if ($existing === null) {
                yield new Slot($doctorId, $start, $end);
            } else {
                // update end only if different
                if ($existing->getEnd() != $end) {
                    $existing->setEnd($end);
                }
                yield $existing;
            }
        }
    }
}
