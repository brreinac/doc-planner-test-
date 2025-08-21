<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Policy\ErrorReportingPolicy;
use App\Policy\ClockInterface;

final class FixedClock implements ClockInterface
{
    private \DateTimeImmutable $dt;
    public function __construct(string $date) { $this->dt = new \DateTimeImmutable($date); }
    public function now(): \DateTimeImmutable { return $this->dt; }
}

final class ErrorReportingPolicyTest extends TestCase
{
    public function testDoesNotReportOnSunday(): void
    {
        $policy = new ErrorReportingPolicy(new FixedClock('2025-08-17')); // Sunday
        $this->assertFalse($policy->shouldReport());
    }

    public function testReportsOnOtherDays(): void
    {
        $policy = new ErrorReportingPolicy(new FixedClock('2025-08-18')); // Monday
        $this->assertTrue($policy->shouldReport());
    }
}


