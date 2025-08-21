<?php
declare(strict_types=1);

namespace App\Policy;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
