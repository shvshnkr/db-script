<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Dbscript\Service\MenuService;
use Symfony\Component\HttpFoundation\Response;

final class MenuApiController
{
    public function __construct(
        private readonly MenuService $menu,
    ) {
    }

    public function list(): Response
    {
        return ApiResponse::ok(['items' => $this->menu->items()]);
    }
}
