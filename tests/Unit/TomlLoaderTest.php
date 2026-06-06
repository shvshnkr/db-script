<?php
declare(strict_types=1);

use Dbscript\Config\TomlLoader;
use PHPUnit\Framework\TestCase;

final class TomlLoaderTest extends TestCase
{
    public function testRoundTripSimpleTable(): void
    {
        $loader = new TomlLoader();
        $data = ['site' => ['version' => '4.5', 'debug' => false]];
        $parsed = $loader->parseString($loader->dump($data), 'roundtrip');

        $this->assertSame('4.5', $parsed['site']['version']);
        $this->assertFalse($parsed['site']['debug']);
    }

    public function testParseFileMissingThrows(): void
    {
        $this->expectException(\Dbscript\Config\TomlConfigException::class);
        (new TomlLoader())->parseFile(sys_get_temp_dir() . '/nonexistent-dbs.toml');
    }
}
