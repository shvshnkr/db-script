<?php
declare(strict_types=1);

namespace Dbscript\Config;

use PhpCollective\Toml\Toml;

final class TomlLoader
{
    /** @return array<string, mixed> */
    public function parseFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new TomlConfigException("TOML file not readable: {$path}");
        }

        try {
            return Toml::decodeFile($path);
        } catch (\Throwable $e) {
            throw new TomlConfigException("Invalid TOML in {$path}: " . $e->getMessage(), 0, $e);
        }
    }

    /** @return array<string, mixed> */
    public function parseString(string $content, string $label = 'inline'): array
    {
        try {
            $data = Toml::decode($content);
        } catch (\Throwable $e) {
            throw new TomlConfigException("Invalid TOML in {$label}: " . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new TomlConfigException("TOML root must be a table: {$label}");
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public function dump(array $data): string
    {
        return Toml::encode($data);
    }

    public function writeFile(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new TomlConfigException("Cannot create directory: {$dir}");
        }

        Toml::encodeFile($path, $data);
    }
}
