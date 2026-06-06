<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsPasswordTest extends TestCase
{
    public function testLegacyHashgenStillVerifies(): void
    {
        $stored = hashgen('TEST');
        $this->assertTrue(dbs_password_verify('TEST', $stored));
        $this->assertFalse(dbs_password_verify('WRONG', $stored));
    }

    public function testModernHashVerifies(): void
    {
        $stored = dbs_password_hash('secret');
        $this->assertTrue(dbs_password_verify('secret', $stored));
        $this->assertFalse(dbs_password_verify('other', $stored));
        $this->assertFalse(dbs_password_needs_rehash($stored));
    }

    public function testLegacyHashNeedsRehash(): void
    {
        $this->assertTrue(dbs_password_needs_rehash(hashgen('TEST')));
    }
}
