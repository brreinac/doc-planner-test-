<?php
declare(strict_types=1);

namespace App\Provider;

use App\DTO\DoctorData;

interface DataProviderInterface
{
    /**
     * Return an iterable of DoctorData for processing.
     *
     * Implementations may return generator or array.
     *
     * @return iterable<DoctorData>
     */
    public function getDoctors(): iterable;
}
