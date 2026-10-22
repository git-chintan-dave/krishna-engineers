<?php

namespace App\Helpers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResponseHelper
{
    /**
     * Return a successful response with a toast message.
     */
    public static function success(
        string $message,
        ?string $route = null,
        array $routeParams = [],
        array $flash = [],
    ): RedirectResponse {
        return self::redirect(
            type: 'success',
            message: $message,
            route: $route,
            routeParams: $routeParams,
            flash: $flash,
        );
    }

    /**
     * Build the redirect response with toast data.
     */
    private static function redirect(
        string $type,
        string $message,
        ?string $route,
        array $routeParams,
        array $flash,
    ): RedirectResponse {
        $toast = [
            'type' => $type,
            'message' => $message,
        ];

        Inertia::flash('toast', $toast);

        $response = empty($route)
            ? redirect()->back()
            : redirect()->route($route, $routeParams);

        return $response->with(
            'toast',
            array_merge($toast, $flash),
        );
    }

    /**
     * Return an error response with a toast message.
     */
    public static function error(
        string $message,
        ?string $route = null,
        array $routeParams = [],
        array $flash = [],
    ): RedirectResponse {
        return self::redirect(
            type: 'error',
            message: $message,
            route: $route,
            routeParams: $routeParams,
            flash: $flash,
        );
    }

    /**
     * Return a warning response with a toast message.
     */
    public static function warning(
        string $message,
        ?string $route = null,
        array $routeParams = [],
        array $flash = [],
    ): RedirectResponse {
        return self::redirect(
            type: 'warning',
            message: $message,
            route: $route,
            routeParams: $routeParams,
            flash: $flash,
        );
    }

    /**
     * Return an informational response with a toast message.
     */
    public static function info(
        string $message,
        ?string $route = null,
        array $routeParams = [],
        array $flash = [],
    ): RedirectResponse {
        return self::redirect(
            type: 'info',
            message: $message,
            route: $route,
            routeParams: $routeParams,
            flash: $flash,
        );
    }
}
