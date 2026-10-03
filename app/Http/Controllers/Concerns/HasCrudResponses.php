<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\ResponseHelper;
use App\Support\FlashMessage;
use Illuminate\Http\RedirectResponse;

trait HasCrudResponses
{
    protected function successResponse(string $action): RedirectResponse
    {
        return ResponseHelper::success(
            message: FlashMessage::success(
                $action,
                self::MODULE_NAME,
            ),
            route: self::INDEX_ROUTE,
        );
    }
}
