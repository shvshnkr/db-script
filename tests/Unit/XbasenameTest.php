<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class XbasenameTest extends TestCase
{
    public function testOnlypathReturnsDirectoryPart(): void
    {
        global $OSTYPE;
        $OSTYPE = 'LINUX';
        $path = '/var/www/html/_data/file.txt';
        $dir = onlypath($path);
        $this->assertIsString($dir);
        $this->assertStringStartsWith('/var/www', $dir);
    }
}
