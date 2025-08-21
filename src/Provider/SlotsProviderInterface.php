<?php
declare(strict_types=1);

namespace App\Provider;

interface SlotsProviderInterface
{
    /**
     * Return slots for a given doctor id.
     *
     * @return iterable<array{start:string,end:string}>
     * @throws \Throwable on failure
     */
    public function getSlotsForDoctor(int $doctorId): iterable;
}
