<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsMysqlParseTest extends TestCase
{
    public function testParseVarcharWithLength(): void
    {
        $parsed = dbs_mysql_parse_type('varchar(255)');
        $this->assertSame('varchar', $parsed['base']);
        $this->assertSame(255, $parsed['len']);
    }

    public function testParseIntWithoutLength(): void
    {
        $parsed = dbs_mysql_parse_type('int');
        $this->assertSame('int', $parsed['base']);
        $this->assertSame(0, $parsed['len']);
    }

    public function testBuildFlagsPrimaryAutoIncrementNotNull(): void
    {
        $flags = dbs_mysql_build_flags([
            'Key' => 'PRI',
            'Extra' => 'auto_increment',
            'Null' => 'NO',
        ]);
        $this->assertStringContainsString('primary_key', $flags);
        $this->assertStringContainsString('auto_increment', $flags);
        $this->assertStringContainsString('not_null', $flags);
    }

    public function testBuildFlagsEmptyRow(): void
    {
        $this->assertSame('', dbs_mysql_build_flags([]));
    }
}
