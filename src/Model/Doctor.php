<?php
declare(strict_types=1);

namespace App\Model;

class Doctor
{
    private int $id;
    private string $name;
    private bool $hasError = false;

    public function __construct(int $id, string $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function markError(): void { $this->hasError = true; }
    public function clearError(): void { $this->hasError = false; }
    public function hasError(): bool { return $this->hasError; }
}
