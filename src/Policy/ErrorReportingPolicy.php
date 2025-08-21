<?php
declare(strict_types=1);

namespace App\Policy;

class ErrorReportingPolicy
{
    private ClockInterface $clock;
    private ?ReportLimiter $limiter;

    public function __construct(ClockInterface $clock, ?ReportLimiter $limiter = null)
    {
        $this->clock = $clock;
        $this->limiter = $limiter;
    }

    public function shouldReport(): bool
    {
        if ($this->clock->now()->format('D') === 'Sun') {
            return false;
        }

        if ($this->limiter !== null) {
            return $this->limiter->allow();
        }

        return true;
    }
}
