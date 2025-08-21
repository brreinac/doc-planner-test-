<?php
declare(strict_types=1);

namespace App\Repository;

use App\Model\Doctor;

interface DoctorRepositoryInterface
{
    public function find(int $id): ?Doctor;
    public function save(Doctor $doctor): void;
}
