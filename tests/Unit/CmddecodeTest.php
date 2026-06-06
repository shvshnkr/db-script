<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CmddecodeTest extends TestCase
{
    public function testPlainSearchTermUnchanged(): void
    {
        global $cmd;
        $cmd = null;
        $this->assertSame('hello', cmddecode('hello'));
    }

    public function testDotCommandSetsCmdArray(): void
    {
        global $cmd;
        $cmd = null;
        cmddecode('.ver');
        $this->assertIsArray($cmd);
        $this->assertSame('ver', $cmd[0]);
    }

    public function testHashPrefixSetsMultisearch(): void
    {
        global $multisearch;
        $multisearch = 0;
        $out = cmddecode('#term');
        $this->assertSame(1, $multisearch);
        $this->assertSame('#term', $out);
    }
}
