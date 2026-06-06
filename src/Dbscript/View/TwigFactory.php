<?php
declare(strict_types=1);

namespace Dbscript\View;

use Dbscript\Application;
use Dbscript\Config\ConfigRepository;
use Dbscript\Config\TomlLoader;
use Dbscript\I18n\LangResolver;
use Dbscript\I18n\MessageCatalog;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigFactory
{
    public static function create(Application $app): TwigRenderer
    {
        $config = $app->config();
        $lang = self::langResolver($config, $app->root())->resolve();
        $catalog = new MessageCatalog($app->root() . '/_langdb', new TomlLoader(), $lang);
        $theme = new ThemeService($config);

        $renderer = new TwigRenderer($app->root() . '/templates');
        $env = $renderer->environment();
        $env->addExtension(new class($catalog, $theme, $config, $lang) extends AbstractExtension {
            public function __construct(
                private readonly MessageCatalog $catalog,
                private readonly ThemeService $theme,
                private readonly ConfigRepository $config,
                private readonly string $lang,
            ) {
            }

            public function getFunctions(): array
            {
                return [
                    new TwigFunction('t', fn (string $key, string $default = '') => $this->catalog->get($key, $default)),
                ];
            }

            public function getGlobals(): array
            {
                $siteName = 'Dbscript';
                $welcome = '';
                if ($this->config->exists('sitedata')) {
                    $site = $this->config->load('sitedata');
                    $siteName = (string) ($site['branding']['site_name'] ?? $siteName);
                    $welcome = (string) ($site['branding']['welcome'] ?? '');
                }

                return [
                    'lang' => $this->lang,
                    'site_name' => $siteName,
                    'welcome' => $welcome,
                    'theme_css' => $this->theme->inlineCss(),
                    'current_user' => $_SESSION['dbs_current_user'] ?? null,
                ];
            }
        });

        return $renderer;
    }

    private static function langResolver(ConfigRepository $config, string $root): LangResolver
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return new LangResolver($config, $root . '/_langdb');
    }
}
