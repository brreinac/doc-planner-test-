<?php
declare(strict_types=1);

namespace App\UnitOfWork;

final class FakeUnitOfWork implements UnitOfWorkInterface
{
    public function begin(): void {}
    public function commit(): void {}
    public function rollback(): void {}
}
