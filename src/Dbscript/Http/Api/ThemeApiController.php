<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\View\ThemeService;
use Symfony\Component\HttpFoundation\Response;

final class ThemeApiController
{
    public function __construct(
        private readonly ThemeService $theme,
    ) {
    }

    public function show(): Response
    {
        return ApiResponse::ok([
            'css_variables' => $this->theme->cssVariables(),
        ]);
    }
}
