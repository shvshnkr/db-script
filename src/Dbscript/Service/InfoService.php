<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\ConfigRepository;

final class InfoService
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array{login?: string, role?: string}|null $user */
    public function page(string $slug, ?array $user = null, ?string $clientIp = null): array
    {
        $slug = ltrim(strtolower(trim($slug)), '.');

        return match ($slug) {
            'ver', 'version' => $this->versionPage(),
            'info' => $this->userInfoPage($user, $clientIp),
            'author' => $this->authorPage(),
            'help' => $this->helpPage($user),
            default => throw new \InvalidArgumentException('Unknown info page: ' . $slug),
        };
    }

    /** @return list<string> */
    public function allowedSlugs(): array
    {
        return ['ver', 'info', 'author', 'help'];
    }

    /** @return array<string, mixed> */
    private function versionPage(): array
    {
        $property = $this->config->load('property');
        $site = $property['site'] ?? [];

        return [
            'slug' => 'ver',
            'title_key' => 'MNU_4',
            'title' => 'Version',
            'lines' => [
                'Core: Dbscript 4 (arch-spa)',
                'Config: ' . (string) ($site['version'] ?? '4.5'),
                'UI: React SPA / REST API v1',
            ],
        ];
    }

    /** @param array{login?: string, role?: string}|null $user @return array<string, mixed> */
    private function userInfoPage(?array $user, ?string $clientIp): array
    {
        if ($user === null || ($user['login'] ?? '') === '') {
            throw new \RuntimeException('Authentication required');
        }

        $role = (string) ($user['role'] ?? 'editor');
        $level = $role === 'admin' ? 10 : 4;

        return [
            'slug' => 'info',
            'title_key' => 'MNU_9',
            'title' => 'User info',
            'lines' => [
                'Login: ' . ($user['login'] ?? ''),
                'Role: ' . $role,
                'Priority level: ' . $level,
                'IP: ' . ($clientIp ?? 'unknown'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function authorPage(): array
    {
        return [
            'slug' => 'author',
            'title_key' => 'MNU_10',
            'title' => 'Author',
            'lines' => [
                'Author: Fufaev A.V. (aka Dj--alex)',
                'Developing: 2006–2026',
                'Site: https://dj-alex.ru',
            ],
        ];
    }

    /** @param array{login?: string, role?: string}|null $user @return array<string, mixed> */
    private function helpPage(?array $user): array
    {
        $commands = ['.ver', '.help', '.info', '.author'];
        if (($user['role'] ?? '') === 'admin') {
            $commands = array_merge($commands, ['.admin', '.edit', '.filemgr']);
        }

        return [
            'slug' => 'help',
            'title_key' => 'HLPINF',
            'title' => 'Help',
            'lines' => array_merge(
                ['Enter .command and help word for additional information.'],
                $commands,
            ),
        ];
    }
}
