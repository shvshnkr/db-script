<?php
declare(strict_types=1);

namespace Dbscript\Auth;

final class CsrfService
{
    private const SESSION_KEY = 'dbs_csrf';

    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function validate(?string $submitted): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($expected) || $expected === '' || !is_string($submitted)) {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    public function hiddenField(): string
    {
        $token = htmlspecialchars($this->token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}
