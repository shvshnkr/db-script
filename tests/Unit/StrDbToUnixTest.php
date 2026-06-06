<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StrDbToUnixTest extends TestCase
{
    public function testParsesDbsDateTime(): void
    {
        $ts = strdbstounixtime('13.04.2010 10:38:53');
        $this->assertSame(1271146733, $ts);
    }

    public function testRoundTripWithDate(): void
    {
        $input = '06.06.2026 12:00:00';
        $ts = strdbstounixtime($input);
        $this->assertGreaterThan(0, $ts);
        $this->assertSame('06.06.2026 12:00:00', date('d.m.Y H:i:s', $ts));
    }
}
