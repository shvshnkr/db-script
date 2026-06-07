<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MycolNumberTest extends TestCase
{
    public function testMycolToNumberAndBack(): void
    {
        $mycol = ['id', 'name', 'value'];
        $n = mycoltonumber($mycol, 'name');
        $this->assertSame(1, $n);
        $this->assertSame('name', numbertomycol($mycol, 1));
    }

    public function testMycolMissingReturnsFalse(): void
    {
        $this->assertFalse(mycoltonumber(['a'], 'missing'));
    }
}
