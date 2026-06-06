<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SmokeScriptsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testVerifyShPasses(): void
    {
        if (!is_executable('/bin/bash')) {
            $this->markTestSkipped('bash not available');
        }
        $out = [];
        $code = 0;
        exec('bash ' . escapeshellarg($this->root . '/scripts/verify.sh') . ' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }
}
