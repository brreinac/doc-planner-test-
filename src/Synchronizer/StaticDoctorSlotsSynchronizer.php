<?php
declare(strict_types=1);

namespace App\Synchronizer;

use App\Provider\Static\StaticDataProviderInterface;
use App\Normalizer\NameNormalizer;
use App\Service\SlotsParser;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\SlotRepositoryInterface;
use App\Policy\ErrorReportingPolicy;
use App\Model\Doctor;

class StaticDoctorSlotsSynchronizer
{
    private StaticDataProviderInterface $provider;
    private NameNormalizer $normalizer;
    private SlotsParser $slotsParser;
    private DoctorRepositoryInterface $doctorRepo;
    private SlotRepositoryInterface $slotRepo;
    private ErrorReportingPolicy $errorPolicy;

    public function __construct(
        StaticDataProviderInterface $provider,
        NameNormalizer $normalizer,
        SlotsParser $slotsParser,
        DoctorRepositoryInterface $doctorRepo,
        SlotRepositoryInterface $slotRepo,
        ErrorReportingPolicy $errorPolicy
    ) {
        $this->provider = $provider;
        $this->normalizer = $normalizer;
        $this->slotsParser = $slotsParser;
        $this->doctorRepo = $doctorRepo;
        $this->slotRepo = $slotRepo;
        $this->errorPolicy = $errorPolicy;
    }

    /**
     * Synchronize data from static provider into persistence layer.
     *
     * - Reuses NameNormalizer and SlotsParser for consistent behavior with dynamic synchronizer.
     * - Marks doctors with errors if slot-processing throws; respects error reporting policy.
     */
    public function synchronize(): void
    {
        try {
            $data = $this->provider->getData();
        } catch (\Throwable $e) {
            // Could not read/parse top-level file; optionally log if policy allows,
            // but we can't mark specific doctors because payload is unavailable.
            if ($this->errorPolicy->shouldReport()) {
                // inject logger in future: $this->logger->error('Static read error', ['error' => $e->getMessage()]);
            }
            return;
        }

        foreach ($data as $rawDoctor) {
            $docId = isset($rawDoctor['id']) ? (int)$rawDoctor['id'] : null;
            $nameRaw = isset($rawDoctor['name']) ? (string)$rawDoctor['name'] : '';

            if ($docId === null) {
                // malformed entry: skip; could log later
                continue;
            }

            $name = $this->normalizer->normalize($nameRaw);

            $doctor = $this->doctorRepo->find($docId) ?? new Doctor($docId, $name);
            $doctor->setName($name);
            $doctor->clearError();
            $this->doctorRepo->save($doctor);

            $slots = $rawDoctor['slots'] ?? [];

            try {
                foreach ($this->slotsParser->parseSlots($slots, $docId) as $slot) {
                    $this->slotRepo->save($slot);
                }
            } catch (\Throwable $e) {
                // mark doctor as error and persist
                $doctor->markError();
                $this->doctorRepo->save($doctor);

                if ($this->errorPolicy->shouldReport()) {
                    // in production: logger->error('Error processing static slots', ['doctorId' => $docId, 'error' => $e->getMessage()]);
                }
                // continue with next doctor
            }
        }
    }
}
