<?php

namespace App\Http\Controllers;

use App\Services\SalaryDashboardService;
use App\Services\StockDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        StockDashboardService $stockDashboardService,
        SalaryDashboardService $salaryDashboardService,
    ): Response {
        return Inertia::render('Dashboard', [
            'salaryDashboard' => Inertia::optional(
                fn () => $salaryDashboardService->getDashboard()
            ),
            // Stocks Tab
            'rawMaterialStock' => Inertia::optional(
                fn () => $stockDashboardService->rawMaterialStock()
            ),
            'toolStock' => Inertia::optional(
                fn () => $stockDashboardService->toolStock()
            ),
        ]);
    }
}
