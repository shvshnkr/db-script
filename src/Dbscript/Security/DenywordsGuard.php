<?php
declare(strict_types=1);

namespace Dbscript\Security;

use Dbscript\Config\ConfigRepository;

final class DenywordsGuard
{
    /** @var list<string> */
    private const BUILTIN = ['information_schema', 'mysql', 'grant'];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    public function assertAllowed(string $query, int $userLevel = 0, bool $superUser = false): void
    {
        if ($superUser) {
            return;
        }

        $blocked = $this->findBlocked($query, $userLevel);
        if ($blocked !== null) {
            throw new \InvalidArgumentException('Denied word: ' . $blocked);
        }
    }

    public function findBlocked(string $query, int $userLevel = 0): ?string
    {
        $lower = strtolower($query);

        foreach (self::BUILTIN as $word) {
            if (str_contains($lower, $word)) {
                return $word;
            }
        }

        foreach ($this->entries() as $entry) {
            $word = $entry['word'];
            if ($word === '' || !str_contains($lower, strtolower($word))) {
                continue;
            }

            // Legacy parity: deny when word plevel - user level > -1
            if (($entry['min_level'] - $userLevel) > -1) {
                return $word;
            }
        }

        return null;
    }

    /** @return list<array{word: string, min_level: int}> */
    private function entries(): array
    {
        if (!$this->config->exists('denywords')) {
            return [];
        }

        $data = $this->config->load('denywords');
        $raw = $data['words'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $out[] = ['word' => $item, 'min_level' => 0];
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $word = (string) ($item['word'] ?? '');
            $out[] = [
                'word' => $word,
                'min_level' => (int) ($item['min_level'] ?? $item['plevel'] ?? 0),
            ];
        }

        return $out;
    }
}
