<?php
declare(strict_types=1);

namespace Tests\Unit\Policy;

use PHPUnit\Framework\TestCase;
use App\Policy\ReportLimiter;

final class ReportLimiterTest extends TestCase
{
    public function testLimiterAllowsOnlyConfiguredReports(): void
    {
        $limiter = new ReportLimiter(2);

        $this->assertTrue($limiter->allow(), 'First report should be allowed');
        $this->assertTrue($limiter->allow(), 'Second report should be allowed');
        $this->assertFalse($limiter->allow(), 'Third report should be denied');
        $this->assertFalse($limiter->allow(), 'Further reports should remain denied');
    }

    public function testZeroMaxReportsDeniesAll(): void
    {
        $limiter = new ReportLimiter(0);

        $this->assertFalse($limiter->allow(), 'With maxReports = 0, no reports should be allowed');
        $this->assertFalse($limiter->allow(), 'Subsequent calls remain denied');
    }

    public function testDefaultMaxReportsIsTen(): void
    {
        $limiter = new ReportLimiter(); // default should be 10

        // allow first 10
        for ($i = 1; $i <= 10; $i++) {
            $this->assertTrue($limiter->allow(), "Report #{$i} should be allowed by default limiter");
        }

        // 11th should be denied
        $this->assertFalse($limiter->allow(), '11th report should be denied by default limiter');
    }
}
