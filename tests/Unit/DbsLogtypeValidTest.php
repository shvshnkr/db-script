<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsLogtypeValidTest extends TestCase
{
    public function testValidLogTypes(): void
    {
        $this->assertTrue(dbs_logtype_valid('_dbs_site_logs', 'site'));
        $this->assertTrue(dbs_logtype_valid('_dbs_site_undo_logs', 'site'));
    }

    public function testRejectsInjection(): void
    {
        $this->assertFalse(dbs_logtype_valid('_dbs_site_evil', 'site'));
        $this->assertFalse(dbs_logtype_valid("'; DROP--", 'site'));
    }
}
