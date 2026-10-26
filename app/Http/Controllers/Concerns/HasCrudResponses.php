<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\ResponseHelper;
use App\Support\FlashMessage;
use Illuminate\Http\RedirectResponse;

trait HasCrudResponses
{
    protected function successResponse(
        string $action,
        ?string $message = null,
        int $created = 0,
        int $updated = 0,
        array $routeParams = [],
    ): RedirectResponse {
        return ResponseHelper::success(
            message: $message ?? FlashMessage::success(
                action: $action,
                module: self::MODULE_NAME,
                created: $created,
                updated: $updated,
            ),
            route: self::INDEX_ROUTE,
            routeParams: $routeParams,
        );
    }

    protected function errorResponse(
        string $action,
        ?string $message = null,
        array $routeParams = [],
    ): RedirectResponse {
        return ResponseHelper::error(
            message: $message ?? FlashMessage::error(
                action: $action,
                module: self::MODULE_NAME,
            ),
            route: self::INDEX_ROUTE,
            routeParams: $routeParams,
        );
    }
}
