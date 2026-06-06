<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TestadminTest extends TestCase
{
    public function testReturnsOneForAdminUser(): void
    {
        $prauth = [
            0 => ['LOGIN'],
            1 => ['alice', 'hash', 0],
            2 => ['bob', 'hash', 1],
            3 => ['carol', 'hash', 0],
        ];
        $this->assertSame(1, testadmin($prauth, 'bob'));
    }

    public function testReturnsZeroForNonAdmin(): void
    {
        $prauth = [
            0 => ['LOGIN'],
            1 => ['alice', 'hash', 0],
            2 => ['bob', 'hash', 0],
        ];
        $this->assertSame(0, testadmin($prauth, 'bob'));
    }

    public function testReturnsZeroForUnknownUser(): void
    {
        $prauth = [
            0 => ['LOGIN'],
            1 => ['alice', 'hash', 1],
        ];
        $this->assertSame(0, testadmin($prauth, 'missing'));
    }
}
