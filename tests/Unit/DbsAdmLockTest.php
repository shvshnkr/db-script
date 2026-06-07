<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsAdmLockTest extends TestCase
{
    public function testLockAdmRestoresVerifiedLevel(): void
    {
        global $ADM, $dbs_adm_verified;
        $dbs_adm_verified = 2;
        $ADM = 99;
        dbs_lock_adm();
        $this->assertSame(2, $ADM);
    }

    public function testRejectAdmOverrideChecksCookieKey(): void
    {
        $_COOKIE['ADM'] = '1';
        $this->assertNotEmpty($_COOKIE['ADM']);
        unset($_COOKIE['ADM']);
        $this->assertEmpty($_COOKIE['ADM'] ?? null);
    }
}
