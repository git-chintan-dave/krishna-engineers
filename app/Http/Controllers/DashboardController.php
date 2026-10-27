<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboardService,
    ): Response {
        $user = $request->user();

        $props = $dashboardService->metadataForUser($user);

        foreach ($dashboardService->deferredPropsForUser($user) as $key => $callback) {
            $props[$key] = Inertia::defer(
                $callback,
                'dashboard-'.$key,
            );
        }

        return Inertia::render('Dashboard', $props);
    }
}
