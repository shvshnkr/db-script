<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CheckboxcorrectTest extends TestCase
{
    public function testUncheckedCheckbox(): void
    {
        ob_start();
        checkboxcorrect('agree', '');
        $html = ob_get_clean();
        $this->assertStringContainsString('type=checkbox', $html);
        $this->assertStringContainsString('name=agree', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testCheckedCheckbox(): void
    {
        ob_start();
        checkboxcorrect('agree', '1');
        $html = ob_get_clean();
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('value=1', $html);
    }

    public function testHashPrefixPreselects(): void
    {
        ob_start();
        checkboxcorrect('agree', '#1');
        $html = ob_get_clean();
        $this->assertStringContainsString('checked', $html);
    }
}
