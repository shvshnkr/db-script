<?php
declare(strict_types=1);

namespace Dbscript\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigRenderer
{
    private Environment $twig;

    public function __construct(string $templatesDir)
    {
        $loader = new FilesystemLoader($templatesDir);
        $this->twig = new Environment($loader, [
            'charset' => 'UTF-8',
            'cache' => false,
            'autoescape' => 'html',
        ]);
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }

    public function environment(): Environment
    {
        return $this->twig;
    }
}
