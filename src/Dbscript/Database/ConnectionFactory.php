<?php
declare(strict_types=1);

namespace Dbscript\Database;

use Dbscript\Config\ConfigRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class ConnectionFactory
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    public function fromSitedata(?array $override = null): Connection
    {
        $site = $override ?? $this->config->load('sitedata');
        $mysql = $site['mysql'] ?? [];

        $host = (string) ($mysql['host'] ?? '127.0.0.1');
        $user = (string) ($mysql['login'] ?? 'root');
        $pass = (string) ($mysql['password'] ?? '');
        $db = (string) ($mysql['database'] ?? '');

        $params = [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'user' => $user,
            'password' => $pass,
            'charset' => 'utf8mb4',
        ];

        if ($db !== '') {
            $params['dbname'] = $db;
        }

        return DriverManager::getConnection($params);
    }

    public function forTable(array $tableMeta, ?Connection $base = null): Connection
    {
        $site = $this->config->load('sitedata');
        $mysql = $site['mysql'] ?? [];

        $host = (string) ($tableMeta['mysql_host'] ?? $mysql['host'] ?? '127.0.0.1');
        $user = (string) ($mysql['login'] ?? 'root');
        $pass = (string) ($mysql['password'] ?? '');
        $db = (string) ($tableMeta['mysql_database'] ?? '');

        if ($base !== null
            && $host === (string) ($mysql['host'] ?? '127.0.0.1')
            && $db !== ''
            && ($mysql['database'] ?? '') === $db
        ) {
            return $base;
        }

        $params = [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'user' => $user,
            'password' => $pass,
            'charset' => 'utf8mb4',
        ];

        if ($db !== '') {
            $params['dbname'] = $db;
        }

        return DriverManager::getConnection($params);
    }
}
