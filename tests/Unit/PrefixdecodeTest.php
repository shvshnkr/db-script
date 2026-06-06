<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PrefixdecodeTest extends TestCase
{
    protected function setUp(): void
    {
        global $pr, $presettedmode, $categorymode, $m6field, $m6count, $mode, $fields;
        $pr = array_fill(0, 100, 0);
        $pr[8] = 1;
        $presettedmode = 0;
        $categorymode = null;
        $m6field = null;
        $m6count = null;
        $mode = null;
        $fields = null;
    }

    public function testEmptyInputDefaultsToShowAllFields(): void
    {
        global $categorymode, $m6count;
        $out = prefixdecode('');
        $this->assertSame('3', $categorymode);
        $this->assertSame(1, $m6count);
        $this->assertSame('', $out);
    }

    public function testBangPrefixSetsCategoryMode(): void
    {
        global $categorymode, $m6field, $m6count;
        $out = prefixdecode('!2col1,col2');
        $this->assertSame('2', $categorymode);
        $this->assertSame(['col1', 'col2'], $m6field);
        $this->assertSame(2, $m6count);
        $this->assertSame('col1,col2', $out);
    }

    public function testPlainFieldListUsesCategoryModeOne(): void
    {
        global $categorymode, $m6count;
        $out = prefixdecode('alpha,beta');
        $this->assertSame('1', $categorymode);
        $this->assertSame(2, $m6count);
        $this->assertSame('alpha,beta', $out);
    }

    public function testPresettedModeThree(): void
    {
        global $presettedmode, $categorymode;
        $presettedmode = 3;
        prefixdecode('x');
        $this->assertSame(3, $categorymode);
    }
}
