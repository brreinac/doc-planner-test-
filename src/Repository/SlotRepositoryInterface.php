<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Slot;

interface SlotRepositoryInterface
{
    /**
     * Find a slot by doctor and start time.
     */
    public function findByDoctorAndStart(int $doctorId, \DateTimeImmutable $start): ?Slot;

    public function save(Slot $slot): void;
}
