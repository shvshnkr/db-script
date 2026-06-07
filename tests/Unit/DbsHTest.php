<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbsHTest extends TestCase
{
    public function testEscapesHtmlInHidekey(): void
    {
        ob_start();
        hidekey('x', '"><script>alert(1)</script>');
        $html = ob_get_clean();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }
}
