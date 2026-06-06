<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HashgenTest extends TestCase
{
    public function testHashgenIsStable(): void
    {
        $a = hashgen('TEST');
        $b = hashgen('TEST');
        $this->assertSame($a, $b);
        $this->assertStringStartsWith('!', $a);
        $this->assertSame(33, strlen($a));
    }

    public function testHashgenDiffersForDifferentPasswords(): void
    {
        $this->assertNotSame(hashgen('A'), hashgen('B'));
    }
}
