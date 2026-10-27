<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        return Inertia::render('Dashboard', [
            'todayShift' => Inertia::defer(
                fn () => $dashboardService->todayShift($user),
            ),
        ]);
    }
}
