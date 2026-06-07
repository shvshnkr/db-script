<?php
declare(strict_types=1);

namespace Dbscript\Service;

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\FilesCfgRepository;

final class FileManagerService
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly FilesCfgRepository $filesCfg,
        private readonly string $root,
    ) {
    }

    /** @param array{login: string, role: string} $user */
    public function listFiles(array $user, ?string $search = null): array
    {
        $items = [];
        foreach ($this->filesCfg->readRows() as $row) {
            $entry = $this->mapRow($row);
            if (!$this->canAccess($entry, $user)) {
                continue;
            }
            if ($search !== null && $search !== '') {
                $name = strtolower($entry['name']);
                if (!str_contains($name, strtolower($search))) {
                    continue;
                }
            }
            if (!is_file($entry['path']) || is_dir($entry['path'])) {
                continue;
            }
            $items[] = $entry;
        }

        return $items;
    }

    /** @param array{login: string, role: string} $user @return array<string, mixed> */
    public function resolveDownload(string $hash, array $user): array
    {
        foreach ($this->filesCfg->readRows() as $row) {
            $entry = $this->mapRow($row);
            if ($entry['hash'] !== $hash && ($row[14] ?? '') !== $hash) {
                continue;
            }
            if (!$this->canAccess($entry, $user)) {
                throw new \RuntimeException('Permission denied');
            }
            if (!is_file($entry['path'])) {
                throw new \InvalidArgumentException('File not found');
            }

            $this->incrementDownloadCount($row);

            return [
                'path' => $entry['path'],
                'filename' => $entry['name'],
                'mime' => $this->guessMime($entry['name']),
            ];
        }

        throw new \InvalidArgumentException('File not found');
    }

    /**
     * @param array{login: string, role: string} $user
     * @return array<string, mixed>
     */
    public function upload(string $originalName, string $tmpPath, array $user): array
    {
        if (!is_uploaded_file($tmpPath) && !is_file($tmpPath)) {
            throw new \InvalidArgumentException('Invalid upload');
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = $this->allowedExtensions();
        if ($allowed !== [] && !in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException('File type not allowed: ' . $ext);
        }

        $uploadDir = $this->uploadDirectory();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($originalName)) ?: 'upload.bin';
        $dest = $uploadDir . '/' . $safeName;
        if (is_file($dest)) {
            $dest = $uploadDir . '/' . time() . '_' . $safeName;
        }

        if (!move_uploaded_file($tmpPath, $dest) && !rename($tmpPath, $dest)) {
            throw new \RuntimeException('Upload failed');
        }

        $hash = substr(md5($dest . microtime(true)), 0, 12);
        $rows = $this->filesCfg->readRows();
        $nextId = 1;
        foreach ($rows as $row) {
            $nextId = max($nextId, (int) ($row[0] ?? 0) + 1);
        }

        $rows[] = [
            (string) $nextId,
            'GENLNK_REG',
            $user['login'],
            '0',
            $hash,
            $dest,
            '',
            $originalName,
            date('d.m.Y H:i:s'),
            '0',
            '',
            '1',
            substr(md5($hash . 'del'), 0, 8),
        ];
        $this->filesCfg->writeRows($rows);

        return $this->mapRow($rows[count($rows) - 1]);
    }

    /** @param array{login: string, role: string} $user */
    public function delete(string $hash, ?string $confirmHash, array $user): bool
    {
        $rows = $this->filesCfg->readRows();
        $isAdmin = ($user['role'] ?? '') === 'admin';

        foreach ($rows as $index => $row) {
            $entry = $this->mapRow($row);
            if ($entry['hash'] !== $hash) {
                continue;
            }
            if (!$this->canAccess($entry, $user) && !$isAdmin) {
                throw new \RuntimeException('Permission denied');
            }

            if (!$isAdmin && $confirmHash !== ($row[12] ?? '')) {
                throw new \InvalidArgumentException('Invalid delete confirmation hash');
            }

            if (is_file($entry['path'])) {
                unlink($entry['path']);
            }

            unset($rows[$index]);
            $this->filesCfg->writeRows(array_values($rows));

            return true;
        }

        throw new \InvalidArgumentException('File not found');
    }

    /** @return list<string> */
    private function allowedExtensions(): array
    {
        $property = $this->config->load('property');
        $fromProperty = $property['paths']['upload_extensions'] ?? [];
        if (is_array($fromProperty) && $fromProperty !== []) {
            return array_map(static fn ($ext): string => strtolower((string) $ext), $fromProperty);
        }

        $files = $this->config->load('files');
        $allowed = $files['allowed'] ?? [];

        return is_array($allowed)
            ? array_map(static fn ($ext): string => strtolower((string) $ext), $allowed)
            : [];
    }

    private function uploadDirectory(): string
    {
        $property = $this->config->load('property');
        $custom = trim((string) ($property['paths']['filemgr'] ?? ''));
        if ($custom !== '' && is_dir($custom)) {
            return rtrim($custom, '/\\');
        }

        return $this->root . '/_local/uploads';
    }

    /** @param list<string> $row @return array<string, mixed> */
    private function mapRow(array $row): array
    {
        $path = (string) ($row[5] ?? '');

        return [
            'id' => (int) ($row[0] ?? 0),
            'hash' => (string) ($row[4] ?? ''),
            'name' => basename($path),
            'path' => $path,
            'share_mode' => (string) ($row[1] ?? ''),
            'owner' => (string) ($row[2] ?? ''),
            'comment' => (string) ($row[7] ?? ''),
            'downloads' => (int) ($row[9] ?? 0),
            'last_download' => (string) ($row[10] ?? ''),
            'searchable' => ($row[11] ?? '') === '1',
            'delete_hash' => (string) ($row[12] ?? ''),
            'size_bytes' => is_file($path) ? (int) filesize($path) : 0,
        ];
    }

    /** @param array<string, mixed> $entry @param array{login: string, role: string} $user */
    private function canAccess(array $entry, array $user): bool
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        $login = $user['login'] ?? '';
        $share = $entry['share_mode'] ?? '';

        return match ($share) {
            'GENLNK_UNREG' => true,
            'GENLNK_REG' => $login !== '' && $login !== 'UNKNOWN',
            'GENLNK_USR' => in_array($login, explode(',', (string) ($entry['owner'] ?? '')), true),
            'GEN_PLVL_USR' => true,
            default => $login === ($entry['owner'] ?? ''),
        };
    }

    /** @param list<string> $row */
    private function incrementDownloadCount(array $row): void
    {
        $rows = $this->filesCfg->readRows();
        foreach ($rows as $index => $existing) {
            if (($existing[4] ?? '') !== ($row[4] ?? '')) {
                continue;
            }
            $rows[$index][9] = (string) ((int) ($existing[9] ?? 0) + 1);
            $rows[$index][10] = date('d.m.Y H:i:s');
            $this->filesCfg->writeRows($rows);

            return;
        }
    }

    private function guessMime(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv' => 'text/csv',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            'html', 'htm' => 'text/html',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
