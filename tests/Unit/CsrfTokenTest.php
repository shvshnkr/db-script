<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        global $pr, $installermode;
        $installermode = false;
        $pr[77] = '';
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        dbs_csrf_boot();
    }

    public function testTokenStablePerSession(): void
    {
        $a = dbs_csrf_token();
        $b = dbs_csrf_token();
        $this->assertSame($a, $b);
        $this->assertSame(32, strlen($a));
    }

    public function testVerifyAcceptsValidToken(): void
    {
        $t = dbs_csrf_token();
        $this->assertTrue(dbs_csrf_verify($t));
        $this->assertFalse(dbs_csrf_verify('bad'));
    }
}
