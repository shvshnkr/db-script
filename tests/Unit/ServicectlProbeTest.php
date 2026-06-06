<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ServicectlProbeTest extends TestCase
{
    public function testActionEnumValidation(): void
    {
        $this->assertTrue(dbs_servicectl_action_valid('db.restart'));
        $this->assertFalse(dbs_servicectl_action_valid('evil;rm'));
    }

    public function testProbeJsonParse(): void
    {
        $json = '{"os_id":"ubuntu","privilege":"none","actions_available":""}';
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertSame('ubuntu', $data['os_id']);
    }

    public function testCmdlineRejectsMetacharacters(): void
    {
        $this->assertFalse(dbs_cmdline_validate('ls; rm -rf /'));
        $this->assertTrue(dbs_cmdline_validate('/usr/local/bin/backup.sh'));
    }
}
