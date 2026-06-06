<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsAuthSessionTest extends TestCase
{
    protected function setUp(): void
    {
        dbs_auth_session_boot();
        $_SESSION = [];
    }

    public function testSessionTokenRoundTrip(): void
    {
        dbs_auth_session_create('TEST', time() + 3600);
        $token = $_SESSION['dbs_auth']['token'] ?? '';
        $this->assertSame(64, strlen($token));
        $this->assertTrue(dbs_auth_session_valid($token));
        $this->assertSame('test', dbs_auth_session_user($token));
    }

    public function testLegacyCookieDetected(): void
    {
        $legacy = base64_encode('user¦pass');
        $this->assertTrue(dbs_auth_cookie_is_legacy($legacy));
        $this->assertFalse(dbs_auth_cookie_is_legacy(bin2hex(random_bytes(32))));
    }

    public function testSessionClear(): void
    {
        dbs_auth_session_create('TEST', time() + 3600);
        $token = $_SESSION['dbs_auth']['token'];
        dbs_auth_session_clear();
        $this->assertFalse(dbs_auth_session_valid($token));
    }
}
