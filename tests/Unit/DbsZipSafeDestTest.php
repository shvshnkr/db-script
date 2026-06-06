<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsZipSafeDestTest extends TestCase
{
    public function testAllowsNormalEntry(): void
    {
        $dest = dbs_zip_entry_dest('/var/www/_local/dump/', 'subdir/file.txt');
        $this->assertSame('/var/www/_local/dump/subdir/file.txt', $dest);
    }

    public function testRejectsParentTraversal(): void
    {
        $this->assertFalse(dbs_zip_entry_dest('/var/www/_local/dump/', '../etc/passwd'));
        $this->assertFalse(dbs_zip_entry_dest('/var/www/_local/dump/', 'ok/../../etc/passwd'));
        $this->assertFalse(dbs_zip_entry_dest('/var/www/_local/dump/', '/etc/passwd'));
    }
}
