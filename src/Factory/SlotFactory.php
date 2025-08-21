<?php
declare(strict_types=1);

namespace App\Factory;

use App\Model\Slot;

class SlotFactory
{
    /**
     * Create a Slot domain object from slot payload.
     * Throws DateTimeException if parsing fails.
     */
    public function create(int $doctorId, string $start, string $end): Slot
    {
        $s = new \DateTimeImmutable($start);
        $e = new \DateTimeImmutable($end);
        return new Slot($doctorId, $s, $e);
    }
}
