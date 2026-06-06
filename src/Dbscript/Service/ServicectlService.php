<?php
declare(strict_types=1);

namespace Dbscript\Service;

final class ServicectlService
{
    public function __construct(
        private readonly string $scriptPath,
    ) {
    }

    /** @return array<string, mixed> */
    public function probe(): array
    {
        return $this->run(['--probe']);
    }

    /** @return array<string, mixed> */
    public function resolve(string $action): array
    {
        return $this->run(['--resolve', $action]);
    }

    /** @param list<string> $args */
    private function run(array $args): array
    {
        if (!is_executable($this->scriptPath)) {
            return ['ok' => false, 'error' => 'servicectl script missing'];
        }

        $cmd = escapeshellarg($this->scriptPath) . ' ' . implode(' ', array_map('escapeshellarg', $args));
        $output = [];
        $code = 0;
        exec($cmd . ' 2>&1', $output, $code);

        return [
            'ok' => $code === 0,
            'code' => $code,
            'output' => implode("\n", $output),
        ];
    }
}
