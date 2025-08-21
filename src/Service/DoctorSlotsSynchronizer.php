<?php
declare(strict_types=1);

namespace App\Service;

use App\Provider\DataProviderInterface;
use App\Factory\DoctorFactory;
use App\Factory\SlotFactory;
use App\Repository\DoctorRepositoryInterface;
use App\Repository\SlotRepositoryInterface;
use App\UnitOfWork\UnitOfWorkInterface;
use App\Normalizer\NameNormalizer;
use App\Policy\ErrorReportingPolicy;
use App\Event\EventDispatcherInterface;
use App\Event\DoctorSyncedEvent;
use App\Exceptions\HttpException;
use App\Exceptions\JsonDecodeException;

/**
 * Unified synchronizer that accepts any DataProvider.
 * Per-doctor operations are executed in a UnitOfWork transaction.
 */
class DoctorSlotsSynchronizer
{
    private DataProviderInterface $provider;
    private DoctorFactory $doctorFactory;
    private SlotFactory $slotFactory;
    private DoctorRepositoryInterface $doctorRepo;
    private SlotRepositoryInterface $slotRepo;
    private UnitOfWorkInterface $uow;
    private NameNormalizer $normalizer;
    private ErrorReportingPolicy $errorPolicy;
    private ?EventDispatcherInterface $events;

    public function __construct(
        DataProviderInterface $provider,
        DoctorFactory $doctorFactory,
        SlotFactory $slotFactory,
        DoctorRepositoryInterface $doctorRepo,
        SlotRepositoryInterface $slotRepo,
        UnitOfWorkInterface $uow,
        NameNormalizer $normalizer,
        ErrorReportingPolicy $errorPolicy,
        ?EventDispatcherInterface $events = null
    ) {
        $this->provider = $provider;
        $this->doctorFactory = $doctorFactory;
        $this->slotFactory = $slotFactory;
        $this->doctorRepo = $doctorRepo;
        $this->slotRepo = $slotRepo;
        $this->uow = $uow;
        $this->normalizer = $normalizer;
        $this->errorPolicy = $errorPolicy;
        $this->events = $events;
    }

    public function synchronize(): void
    {
        foreach ($this->provider->getDoctors() as $doctorData) {
            $docId = $doctorData->getId();
            $name = $this->normalizer->normalize($doctorData->getName());

            // create or update doctor entity
            $doctor = $this->doctorRepo->find($docId) ?? $this->doctorFactory->create($docId, $name);
            $doctor->setName($name);
            $doctor->clearError();

            $created = 0;
            $updated = 0;

            $this->uow->begin();
            try {
                $this->doctorRepo->save($doctor);

                // fetch slots via DTO callable; this may throw HttpException/JsonDecodeException or others
                $slotsIterable = $doctorData->getSlots();

                foreach ($slotsIterable as $slotPayload) {
                    if (!isset($slotPayload['start']) || !isset($slotPayload['end'])) {
                        continue;
                    }
                    $slotObj = $this->slotFactory->create($docId, $slotPayload['start'], $slotPayload['end']);

                    $existing = $this->slotRepo->findByDoctorAndStart($docId, $slotObj->getStart());

                    if ($existing === null) {
                        $this->slotRepo->save($slotObj);
                        $created++;
                    } else {
                        if ($existing->getEnd() != $slotObj->getEnd()) {
                            $existing->setEnd($slotObj->getEnd());
                            $this->slotRepo->save($existing);
                            $updated++;
                        }
                    }
                }

                $this->uow->commit();

                // dispatch domain event for successful doctor sync
                if ($this->events !== null) {
                    $this->events->dispatch(new DoctorSyncedEvent($docId, $created, $updated));
                }
            } catch (\Throwable $e) {
                $this->uow->rollback();

                // mark doctor error and persist
                $doctor->markError();
                // try to persist doctor error outside the transaction — best-effort
                try {
                    $this->doctorRepo->save($doctor);
                } catch (\Throwable $_) {
                    // swallow to avoid escalation during sync; logging is recommended
                }

                // optionally report/log only on allowed days
                if ($this->errorPolicy->shouldReport()) {
                    // inject logger or event for alerting; left as comment for now
                }

                // continue with next doctor
            }
        }
    }
}
