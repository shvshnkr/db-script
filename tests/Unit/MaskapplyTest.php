<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MaskapplyTest extends TestCase
{
    public function testMaskMatchesExtension(): void
    {
        $this->assertSame('file.txt', maskapply('file.txt', '*.txt'));
        $this->assertFalse(maskapply('file.log', '*.txt'));
    }

    public function testEmptyMaskPasses(): void
    {
        $this->assertSame('any.name', maskapply('any.name', ''));
    }
}
