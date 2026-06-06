<?php
declare(strict_types=1);

namespace Dbscript\Config;

final class ConfigRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function __construct(
        private readonly string $confDir,
        private readonly TomlLoader $toml,
    ) {
    }

    /** @return array<string, mixed> */
    public function load(string $name): array
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $tomlPath = $this->confDir . '/' . $name . '.toml';
        if (is_file($tomlPath)) {
            return $this->cache[$name] = $this->toml->parseFile($tomlPath);
        }

        throw new TomlConfigException("Config not found: {$name}.toml in {$this->confDir}");
    }

    /** @param array<string, mixed> $data */
    public function save(string $name, array $data): void
    {
        $this->toml->writeFile($this->confDir . '/' . $name . '.toml', $data);
        $this->cache[$name] = $data;
    }

    public function exists(string $name): bool
    {
        return is_file($this->confDir . '/' . $name . '.toml');
    }

    public function confDir(): string
    {
        return $this->confDir;
    }
}
