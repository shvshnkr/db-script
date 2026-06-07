<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsEscapeTest extends TestCase
{
    public function testEscapeIdentValid(): void
    {
        $this->assertSame('table_name', dbs_escape_ident('table_name'));
        $this->assertSame('`db`.`tbl`', dbs_escape_ident('`db`.`tbl`'));
    }

    public function testEscapeIdentRejectsInjection(): void
    {
        $this->assertFalse(dbs_escape_ident("x'; DROP--"));
    }

    public function testEscapeValueWithoutConnect(): void
    {
        $this->assertSame("O\\'Reilly", dbs_escape_value(null, "O'Reilly"));
    }
}
