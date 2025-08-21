<?php
declare(strict_types=1);

namespace App\DTO;

/**
 * Lightweight DTO representing a doctor and a way to obtain slots for it.
 * The $slotsFetcher is a callable returning an array|iterable of slot arrays
 * (each item ['start'=>..., 'end'=>...]) or throwing on error.
 */
final class DoctorData
{
    private int $id;
    private string $name;
    /** @var callable(): iterable|array */
    private $slotsFetcher;

    /**
     * @param callable(): iterable|array $slotsFetcher
     */
    public function __construct(int $id, string $name, callable $slotsFetcher)
    {
        $this->id = $id;
        $this->name = $name;
        $this->slotsFetcher = $slotsFetcher;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }

    /**
     * Execute the fetcher and return an iterable (array or generator).
     *
     * May throw if the underlying provider fails (e.g. HTTP or JSON error).
     *
     * @return iterable<array>
     */
    public function getSlots(): iterable
    {
        $f = $this->slotsFetcher;
        return $f();
    }
}
