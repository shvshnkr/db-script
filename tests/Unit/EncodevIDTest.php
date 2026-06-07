<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EncodevIDTest extends TestCase
{
    public function testEncodeDecodeRoundTrip(): void
    {
        $raw = 'hello world';
        $enc = encodevID($raw);
        $this->assertStringStartsWith('!H', $enc);
        $decoded = trim(decodevID($enc));
        $this->assertSame($raw, $decoded);
    }

    public function testEncodeSpaces(): void
    {
        $this->assertStringContainsString('%20', encodevID('a b'));
    }
}
