<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\ConfigRepository;

final class MenuService
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        $pages = $this->config->load('pages');
        $configured = $pages['pages'] ?? [];
        if (is_array($configured) && $configured !== []) {
            return $this->mapConfiguredPages($configured);
        }

        return $this->defaultItems();
    }

    /** @return list<array<string, mixed>> */
    private function defaultItems(): array
    {
        return [
            [
                'id' => 'editor',
                'label_key' => 'MNU_2',
                'label' => 'Editor',
                'href' => '/app/editor',
                'spa' => true,
            ],
            [
                'id' => 'reader',
                'label_key' => 'MNU_3',
                'label' => 'Search',
                'href' => '/app/reader',
                'spa' => true,
            ],
            [
                'id' => 'version',
                'label_key' => 'MNU_4',
                'label' => 'Version',
                'href' => '/app/info/ver',
                'spa' => true,
            ],
            [
                'id' => 'files',
                'label_key' => 'MNU_7',
                'label' => 'Filemanager',
                'href' => '/app/files',
                'spa' => true,
            ],
            [
                'id' => 'converter',
                'label_key' => 'A_IMPEXP',
                'label' => 'Import Export',
                'href' => '/app/converter',
                'spa' => true,
            ],
            [
                'id' => 'admin',
                'label_key' => 'MNU_0',
                'label' => 'Admin',
                'href' => '/admin-arch.php',
                'spa' => false,
            ],
        ];
    }

    /** @param list<array<string, mixed>> $configured @return list<array<string, mixed>> */
    private function mapConfiguredPages(array $configured): array
    {
        $items = [];
        foreach ($configured as $page) {
            if (!is_array($page)) {
                continue;
            }

            $legacyHref = (string) ($page['href'] ?? $page['url'] ?? '');
            $spaHref = $this->mapLegacyHref($legacyHref);
            if ($spaHref === null) {
                continue;
            }

            $items[] = [
                'id' => (string) ($page['id'] ?? count($items) + 1),
                'label_key' => (string) ($page['label_key'] ?? ''),
                'label' => (string) ($page['label'] ?? $page['title'] ?? ''),
                'href' => $spaHref['href'],
                'spa' => $spaHref['spa'],
            ];
        }

        return $items !== [] ? $items : $this->defaultItems();
    }

    /** @return array{href: string, spa: bool}|null */
    private function mapLegacyHref(string $href): ?array
    {
        if ($href === '') {
            return null;
        }

        if (str_contains($href, 'w.php') || str_contains($href, 'w-arch.php')) {
            return ['href' => '/app/editor', 'spa' => true];
        }
        if (str_contains($href, 'r.php') || str_contains($href, 'r-arch.php')) {
            if (str_contains($href, '.ver')) {
                return ['href' => '/app/info/ver', 'spa' => true];
            }
            if (str_contains($href, '.info')) {
                return ['href' => '/app/info/info', 'spa' => true];
            }
            if (str_contains($href, '.author')) {
                return ['href' => '/app/info/author', 'spa' => true];
            }
            if (str_contains($href, '.help')) {
                return ['href' => '/app/info/help', 'spa' => true];
            }

            return ['href' => '/app/reader', 'spa' => true];
        }
        if (str_contains($href, 'filemgr.php')) {
            return ['href' => '/app/files', 'spa' => true];
        }
        if (str_contains($href, 'ietbl') || str_contains($href, 'A_IMPEXP')) {
            return ['href' => '/app/converter', 'spa' => true];
        }
        if (str_contains($href, 'admin')) {
            return ['href' => '/admin-arch.php', 'spa' => false];
        }

        if (str_starts_with($href, '/app/')) {
            return ['href' => $href, 'spa' => true];
        }

        return ['href' => '/' . ltrim($href, '/'), 'spa' => false];
    }
}
