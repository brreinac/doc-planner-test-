<?php
declare(strict_types=1);

namespace App\Policy;

/**
 * Simple in-memory report limiter per run.
 * Allows up to $maxReports calls to allow(), then denies further reports.
 * This is intentionally simple and reset per process run (not persisted).
 */
class ReportLimiter
{
    private int $maxReports;
    private int $count = 0;

    public function __construct(int $maxReports = 10)
    {
        $this->maxReports = max(0, $maxReports);
    }

    public function allow(): bool
    {
        if ($this->count < $this->maxReports) {
            $this->count++;
            return true;
        }
        return false;
    }
}
