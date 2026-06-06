<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;
use Dbscript\Config\DbdataRepository;
use Dbscript\Config\UserRepository;

/**
 * Temporary bridge: exposes legacy globals ($pr, $prauth, $prdbdata) from arch-modern services.
 */
final class GlobalBridge
{
    /** @var array<int, mixed>|null */
    private static ?array $pr = null;

    /** @var array<string, array<int, mixed>>|null */
    private static ?array $prauth = null;

    /** @var list<array<int, mixed>>|null */
    private static ?array $prdbdata = null;

    private static int $prdbdatacnt = 0;

    private static ?string $adm = null;

    public static function hydrate(Application $app): void
    {
        if (self::$pr === null && $app->config()->exists('property')) {
            self::$pr = self::propertyToLegacyArray($app->property());
        }

        if (self::$prdbdata === null && $app->config()->exists('dbdata')) {
            $repo = new DbdataRepository($app->config());
            [, , self::$prdbdata, self::$prdbdatacnt] = $repo->toLegacyBundle();
        }

        if (self::$prauth === null && $app->config()->exists('users')) {
            self::$prauth = self::usersToLegacyAuth(new UserRepository($app->config()));
        }
    }

    /** @param array<string, mixed> $property */
    private static function propertyToLegacyArray(array $property): array
    {
        $pr = array_fill(0, 256, '');
        $map = $property['legacy_index'] ?? [];
        if (is_array($map)) {
            foreach ($map as $idx => $value) {
                if (is_int($idx) || ctype_digit((string) $idx)) {
                    $pr[(int) $idx] = is_bool($value) ? ($value ? 'on' : 'off') : (string) $value;
                }
            }
        }

        return $pr;
    }

    /** @return array<string, array<int, mixed>> */
    private static function usersToLegacyAuth(UserRepository $users): array
    {
        $prauth = [];
        foreach ($users->all() as $user) {
            $prauth[$user['login']] = [
                0 => $user['login'],
                1 => $user['password_hash'],
                2 => $user['role'],
                3 => $user['role'] === 'admin' ? 'SU' : 'editor',
            ];
        }

        return $prauth;
    }

    /** @return array<int, mixed> */
    public static function pr(): array
    {
        return self::$pr ?? array_fill(0, 256, '');
    }

    /** @return array<string, array<int, mixed>> */
    public static function prauth(): array
    {
        return self::$prauth ?? [];
    }

    /** @return list<array<int, mixed>> */
    public static function prdbdata(): array
    {
        return self::$prdbdata ?? [[]];
    }

    public static function prdbdatacnt(): int
    {
        return self::$prdbdatacnt;
    }

    public static function adm(): ?string
    {
        return self::$adm;
    }

    public static function setAdm(string $login): void
    {
        self::$adm = $login;
    }

    /** Push hydrated values into global scope for legacy includes. */
    public static function exportGlobals(): void
    {
        $GLOBALS['pr'] = self::pr();
        $GLOBALS['prauth'] = self::prauth();
        $GLOBALS['prdbdata'] = self::prdbdata();
        $GLOBALS['prdbdatacnt'] = self::prdbdatacnt();
        if (self::$adm !== null) {
            $GLOBALS['ADM'] = self::$adm;
        }
    }
}
