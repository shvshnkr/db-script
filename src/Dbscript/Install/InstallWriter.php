<?php
declare(strict_types=1);

namespace Dbscript\Install;

use Dbscript\Config\ConfigRepository;
use Dbscript\Config\UserRepository;

final class InstallWriter
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly UserRepository $users,
    ) {
    }

    public function writeFreshInstall(
        string $adminLogin,
        string $adminPassword,
        string $mysqlHost = '127.0.0.1',
        string $mysqlUser = 'root',
        string $mysqlPass = '',
        string $lang = 'english',
    ): void {
        $jwtSecret = bin2hex(random_bytes(32));

        $this->config->save('secrets', ['jwt_secret' => $jwtSecret]);

        $this->config->save('property', [
            'site' => ['version' => '4.5', 'charset' => 'utf-8', 'debug' => false],
            'security' => ['csrf_enabled' => true, 'basic_auth_gate' => false],
            'paths' => ['filemgr' => '', 'upload_extensions' => ['html', 'gif', 'bmp', 'png']],
            'mysql' => ['default_host' => $mysqlHost, 'default_port' => 3306],
            'legacy_index' => [8 => 1, 36 => 'off', 41 => '', 43 => $mysqlHost],
        ]);

        $this->config->save('sitedata', [
            'branding' => ['logo' => 'dbslogo.gif', 'welcome' => 'Welcome to Dbscript.'],
            'mysql' => ['login' => $mysqlUser, 'password' => $mysqlPass, 'host' => $mysqlHost],
            'search' => ['mode1' => 'by name', 'mode2' => 'by code', 'mode3' => 'show all'],
        ]);

        $this->config->save('langset', ['default' => $lang, 'available' => ['english', 'russian']]);

        $this->config->save('dbdata', ['tables' => []]);
        $this->config->save('pages', ['pages' => []]);
        $this->config->save('styles', ['theme' => ['bg' => '#f4f4f8', 'fg' => '#1a1a2e', 'accent' => '#0000af']]);
        $this->config->save('denywords', ['words' => ['drop', 'truncate']]);
        $this->config->save('files', ['allowed' => ['html', 'gif', 'bmp', 'png']]);

        $this->users->saveAll([[
            'login' => $adminLogin,
            'password_hash' => $this->users->hashPassword($adminPassword),
            'role' => 'admin',
            'active' => true,
        ]]);
    }
}
