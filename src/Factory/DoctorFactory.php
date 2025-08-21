<?php
declare(strict_types=1);

namespace App\Factory;

use App\Model\Doctor;

class DoctorFactory
{
    /**
     * Create domain Doctor from id + name applying normalization/validation rules if needed.
     */
    public function create(int $id, string $name): Doctor
    {
        // Additional logic (validation, defaulting) can be added here.
        return new Doctor($id, $name);
    }
}
