<?php
declare(strict_types=1);

namespace Dbscript;

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;

final class Application
{
    private static ?self $instance = null;

    private function __construct(
        private readonly string $root,
        private readonly TomlLoader $toml,
        private readonly ConfigRepository $config,
    ) {
    }

    public static function boot(string $root): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $toml = new TomlLoader();
        $config = new ConfigRepository($root . '/_conf', $toml);

        self::$instance = new self($root, $toml, $config);

        return self::$instance;
    }

    /** @internal PHPUnit only */
    public static function resetForTests(): void
    {
        self::$instance = null;
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application not booted; require bootstrap.php first.');
        }

        return self::$instance;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function toml(): TomlLoader
    {
        return $this->toml;
    }

    public function config(): ConfigRepository
    {
        return $this->config;
    }

    /** @return array<string, mixed> */
    public function property(): array
    {
        return $this->config->load('property');
    }

    public function isInstalled(): bool
    {
        return is_file($this->root . '/_conf/property.toml')
            || is_file($this->root . '/_conf/property.cfg');
    }
}
