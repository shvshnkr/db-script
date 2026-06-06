<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ClearArrayEmptyTest extends TestCase
{
    public function testRemovesEmptyAndTrims(): void
    {
        $in = ['  a  ', '', 'b', '   ', 'c'];
        // Whitespace-only values pass empty() but trim to '' (legacy behavior).
        $this->assertSame(['a', 'b', '', 'c'], Clear_array_empty($in));
    }

    public function testEmptyInput(): void
    {
        $this->assertSame([], Clear_array_empty([]));
    }
}
