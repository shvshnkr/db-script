<?php
declare(strict_types=1);

use Dbscript\Auth\CsrfService;
use PHPUnit\Framework\TestCase;

final class CsrfServiceArchTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testTokenAndValidate(): void
    {
        $csrf = new CsrfService(true);
        $token = $csrf->token();
        $this->assertSame(64, strlen($token));
        $this->assertTrue($csrf->validate($token));
        $this->assertFalse($csrf->validate('wrong'));
    }

    public function testDisabledAlwaysValidates(): void
    {
        $csrf = new CsrfService(false);
        $this->assertTrue($csrf->validate(null));
    }
}
