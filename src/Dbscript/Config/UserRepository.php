<?php
declare(strict_types=1);

namespace Dbscript\Config;

final class UserRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /** @return list<array{login: string, password_hash: string, role: string, active: bool}> */
    public function all(): array
    {
        $data = $this->config->load('users');
        $users = $data['users'] ?? [];

        if (!is_array($users)) {
            return [];
        }

        $out = [];
        foreach ($users as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'login' => (string) ($row['login'] ?? ''),
                'password_hash' => (string) ($row['password_hash'] ?? ''),
                'role' => (string) ($row['role'] ?? 'editor'),
                'active' => (bool) ($row['active'] ?? true),
            ];
        }

        return $out;
    }

    public function find(string $login): ?array
    {
        foreach ($this->all() as $user) {
            if ($user['login'] === $login && $user['active']) {
                return $user;
            }
        }

        return null;
    }

    public function verifyPassword(string $login, string $plain): bool
    {
        $user = $this->find($login);
        if ($user === null || $user['password_hash'] === '') {
            return false;
        }

        return password_verify($plain, $user['password_hash']);
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /** @param list<array{login: string, password_hash: string, role: string, active: bool}> $users */
    public function saveAll(array $users): void
    {
        $this->config->save('users', ['users' => $users]);
    }
}
