<?php
declare(strict_types=1);

namespace Dbscript\I18n;

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;

final class MessageCatalog
{
    /** @var array<string, string> */
    private array $messages = [];

    public function __construct(
        private readonly string $langDir,
        private readonly TomlLoader $toml,
        private readonly string $language,
    ) {
        $this->load($language);
    }

    public function language(): string
    {
        return $this->language;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->messages[$key] ?? ($default !== '' ? $default : $key);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->messages;
    }

    private function load(string $language): void
    {
        $jsonPath = $this->langDir . '/' . $language . '.json';
        $tomlPath = $this->langDir . '/' . $language . '.toml';
        $cfgPath = $this->langDir . '/' . $language . '.cfg';

        if (is_file($jsonPath)) {
            $raw = file_get_contents($jsonPath);
            if ($raw !== false) {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $messages = $data['messages'] ?? [];
                if (is_array($messages)) {
                    foreach ($messages as $key => $value) {
                        if (is_string($key) && (is_string($value) || is_numeric($value))) {
                            $this->messages[$key] = (string) $value;
                        }
                    }
                }
            }

            return;
        }

        if (is_file($tomlPath)) {
            $data = $this->toml->parseFile($tomlPath);
            if (isset($data['entries']) && is_array($data['entries'])) {
                foreach ($data['entries'] as $row) {
                    if (is_array($row) && isset($row['key'], $row['value'])) {
                        $this->messages[(string) $row['key']] = (string) $row['value'];
                    }
                }
            }
            $messages = $data['messages'] ?? [];
            if (is_array($messages)) {
                foreach ($messages as $key => $value) {
                    if (is_string($key) && (is_string($value) || is_numeric($value))) {
                        $this->messages[$key] = (string) $value;
                    }
                }
            }

            return;
        }

        if (is_file($cfgPath)) {
            $this->messages = self::parseLegacyCfg($cfgPath);

            return;
        }

        if ($language !== 'english' && is_file($this->langDir . '/english.toml')) {
            $fallback = new self($this->langDir, $this->toml, 'english');
            $this->messages = $fallback->all();
        }
    }

    /** @return array<string, string> */
    public static function parseLegacyCfg(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, '<-')) {
                continue;
            }
            $semi = strpos($line, ';');
            if ($semi === false) {
                continue;
            }
            $key = rtrim(substr($line, 0, $semi), ':');
            $value = trim(substr($line, $semi + 1));
            if ($key === '' || $key === 'NONE' || str_starts_with($key, 'English text') || str_starts_with($key, 'Русский текст')) {
                continue;
            }
            if (!mb_check_encoding($key, 'UTF-8')) {
                $key = self::toUtf8($key);
            }
            if (!mb_check_encoding($value, 'UTF-8')) {
                $value = self::toUtf8($value);
            }
            $out[$key] = $value;
        }

        return $out;
    }

    private static function toUtf8(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }
        $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);

        return is_string($converted) ? $converted : $text;
    }
}
