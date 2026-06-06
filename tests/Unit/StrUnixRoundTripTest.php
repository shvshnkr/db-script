<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StrUnixRoundTripTest extends TestCase
{
    public function testUnixToDbsAndBack(): void
    {
        $ts = mktime(12, 30, 45, 6, 7, 2026);
        $dbs = strunixtimetodbs($ts);
        $back = strdbstounixtime($dbs);
        $this->assertSame($ts, $back);
    }
}
