<?php
declare(strict_types=1);

namespace Dbscript\View;

use Dbscript\Config\ConfigRepository;
use Dbscript\I18n\MessageCatalog;

final class ThemeService
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /** @return array<string, string> */
    public function cssVariables(): array
    {
        if (!$this->config->exists('styles')) {
            return [
                '--dbs-bg' => '#f4f4f8',
                '--dbs-fg' => '#1a1a2e',
                '--dbs-accent' => '#0000af',
                '--dbs-border' => '#c8c8d8',
            ];
        }

        $styles = $this->config->load('styles');
        $theme = $styles['theme'] ?? [];

        return [
            '--dbs-bg' => (string) ($theme['bg'] ?? '#f4f4f8'),
            '--dbs-fg' => (string) ($theme['fg'] ?? '#1a1a2e'),
            '--dbs-accent' => (string) ($theme['accent'] ?? '#0000af'),
            '--dbs-border' => (string) ($theme['border'] ?? '#c8c8d8'),
        ];
    }

    public function inlineCss(): string
    {
        $vars = $this->cssVariables();
        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = $name . ': ' . $value . ';';
        }

        return ':root {' . implode(' ', $lines) . '}';
    }
}
