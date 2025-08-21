<?php
declare(strict_types=1);

namespace App\Event;

final class DoctorSyncedEvent
{
    private int $doctorId;
    private int $slotsCreated;
    private int $slotsUpdated;

    public function __construct(int $doctorId, int $slotsCreated, int $slotsUpdated)
    {
        $this->doctorId = $doctorId;
        $this->slotsCreated = $slotsCreated;
        $this->slotsUpdated = $slotsUpdated;
    }

    public function getDoctorId(): int { return $this->doctorId; }
    public function getSlotsCreated(): int { return $this->slotsCreated; }
    public function getSlotsUpdated(): int { return $this->slotsUpdated; }
}
