<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Application;
use Dbscript\Config\TomlLoader;
use Dbscript\I18n\LangResolver;
use Dbscript\I18n\MessageCatalog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class I18nApiController
{
    public function __construct(
        private readonly LangResolver $langResolver,
    ) {
    }

    public function bundle(Request $request): Response
    {
        $requested = trim((string) $request->query->get('lang', ''));
        $lang = $requested !== '' && $this->isAllowed($requested)
            ? $requested
            : $this->langResolver->resolve();

        $app = Application::get();
        $catalog = new MessageCatalog($app->root() . '/_langdb', new TomlLoader(), $lang);

        return ApiResponse::ok([
            'language' => $lang,
            'messages' => $catalog->all(),
        ]);
    }

    public function languages(): Response
    {
        $app = Application::get();
        $langset = $app->config()->load('langset');
        $available = $langset['available'] ?? ['english', 'russian'];
        if (!is_array($available)) {
            $available = ['english'];
        }

        return ApiResponse::ok([
            'default' => (string) ($langset['default'] ?? 'english'),
            'available' => array_values(array_map(static fn ($lang): string => (string) $lang, $available)),
        ]);
    }

    private function isAllowed(string $lang): bool
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $lang)) {
            return false;
        }

        $dir = Application::get()->root() . '/_langdb';

        return is_file($dir . '/' . $lang . '.json')
            || is_file($dir . '/' . $lang . '.toml')
            || is_file($dir . '/' . $lang . '.cfg');
    }
}
