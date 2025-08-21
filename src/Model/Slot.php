<?php
declare(strict_types=1);

namespace App\Model;

class Slot
{
    private int $doctorId;
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct(int $doctorId, \DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        $this->doctorId = $doctorId;
        $this->start = $start;
        $this->end = $end;
    }

    public function getDoctorId(): int { return $this->doctorId; }
    public function getStart(): \DateTimeImmutable { return $this->start; }
    public function getEnd(): \DateTimeImmutable { return $this->end; }

    public function setEnd(\DateTimeImmutable $end): void { $this->end = $end; }

    /**
     * Helper: two slots are equal by doctorId and start timestamp.
     */
    public function equalsByDoctorAndStart(int $doctorId, \DateTimeImmutable $start): bool
    {
        return $this->doctorId === $doctorId && $this->start == $start;
    }
}
