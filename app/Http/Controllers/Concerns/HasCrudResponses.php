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
    ): RedirectResponse {
        return ResponseHelper::success(
            message: $message ?? FlashMessage::success(
            $action,
            self::MODULE_NAME,
        ),
            route: self::INDEX_ROUTE,
        );
    }

    protected function errorResponse(
        string $action,
        ?string $message = null,
    ): RedirectResponse {
        return ResponseHelper::error(
            message: $message ?? FlashMessage::error(
            $action,
            self::MODULE_NAME,
        ),
            route: self::INDEX_ROUTE,
        );
    }
}
