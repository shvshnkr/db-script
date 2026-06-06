<?php
declare(strict_types=1);

namespace Dbscript\Http;

use Dbscript\Application;

/**
 * Temporary bridge: exposes legacy globals ($pr, $prauth, $ADM) from arch-modern services.
 * Removed when w.php / r.php become thin controllers.
 */
final class GlobalBridge
{
    /** @var array<int, mixed>|null */
    private static ?array $pr = null;

    /** @var array<string, array<int, mixed>>|null */
    private static ?array $prauth = null;

    private static ?string $adm = null;

    public static function hydrateFromToml(Application $app): void
    {
        if (!self::$pr && $app->config()->exists('property')) {
            self::$pr = self::propertyToLegacyArray($app->property());
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
                    $pr[(int) $idx] = $value;
                }
            }
        }

        return $pr;
    }

    /** @return array<int, mixed> */
    public static function pr(): array
    {
        return self::$pr ?? [];
    }

    public static function adm(): ?string
    {
        return self::$adm;
    }

    public static function setAdm(string $login): void
    {
        self::$adm = $login;
    }
}
