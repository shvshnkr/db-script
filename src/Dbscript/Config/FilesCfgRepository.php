<?php
declare(strict_types=1);

namespace Dbscript\Config;

final class FilesCfgRepository
{
    private const DELIM = '¦';

    public function __construct(
        private readonly string $confDir,
    ) {
    }

    public function path(): string
    {
        return $this->confDir . '/files.cfg';
    }

    /** @return list<list<string>> */
    public function readRows(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            $this->ensureFile();

            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false || count($lines) < 2) {
            return [];
        }

        $rows = [];
        for ($i = 2, $n = count($lines); $i < $n; $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $row = explode(self::DELIM, $line);
            if (($row[0] ?? '') === '' || ($row[0] ?? '') === '0') {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @param list<list<string>> $rows */
    public function writeRows(array $rows): void
    {
        $this->ensureDir();
        $path = $this->path();
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Cannot write files.cfg');
        }

        $eol = PHP_EOL;
        fwrite($handle, 'ID' . self::DELIM . 'Sharemode' . self::DELIM . 'USRLIST' . self::DELIM . 'PLVL' . self::DELIM . 'HASH' . self::DELIM . 'FILE' . self::DELIM . 'rmvset' . self::DELIM . 'comment' . self::DELIM . 'data' . self::DELIM . 'All download' . self::DELIM . 'Last download' . self::DELIM . 'Allow search' . self::DELIM . 'HashDel' . $eol);
        fwrite($handle, '0' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . 'd' . self::DELIM . 'd' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . '0' . self::DELIM . 'd' . self::DELIM . 'd' . $eol);

        foreach ($rows as $row) {
            while (count($row) < 13) {
                $row[] = '';
            }
            fwrite($handle, implode(self::DELIM, $row) . $eol);
        }

        fclose($handle);
    }

    private function ensureFile(): void
    {
        $this->ensureDir();

        if (is_file($this->path())) {
            return;
        }

        $this->writeRows([]);
    }

    private function ensureDir(): void
    {
        if (!is_dir($this->confDir)) {
            mkdir($this->confDir, 0775, true);
        }
    }
}
