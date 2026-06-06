<?php
declare(strict_types=1);

namespace Dbscript\I18n;

use Dbscript\Application;
use Dbscript\Config\ConfigRepository;

final class LangResolver
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly string $langDir,
    ) {
    }

    public function resolve(): string
    {
        if (isset($_GET['lang']) && is_string($_GET['lang']) && $this->isAllowed($_GET['lang'])) {
            $_SESSION['dbs_lang'] = $_GET['lang'];
            return $_GET['lang'];
        }

        if (isset($_SESSION['dbs_lang']) && is_string($_SESSION['dbs_lang']) && $this->isAllowed($_SESSION['dbs_lang'])) {
            return $_SESSION['dbs_lang'];
        }

        if ($this->config->exists('langset')) {
            $langset = $this->config->load('langset');
            $default = (string) ($langset['default'] ?? 'english');
            if ($this->isAllowed($default)) {
                return $default;
            }
        }

        return 'english';
    }

    private function isAllowed(string $lang): bool
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $lang)) {
            return false;
        }

        return is_file($this->langDir . '/' . $lang . '.json')
            || is_file($this->langDir . '/' . $lang . '.toml')
            || is_file($this->langDir . '/' . $lang . '.cfg');
    }
}
