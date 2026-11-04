<?php

namespace App\Http\Controllers;

use App\Services\AttendanceDashboardService;
use App\Services\ProductionDashboardService;
use App\Services\SalaryDashboardService;
use App\Services\StockDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AttendanceDashboardService $attendanceDashboardService,
        ProductionDashboardService $productionDashboardService,
        SalaryDashboardService $salaryDashboardService,
        StockDashboardService $stockDashboardService,
    ): Response {
        $user = $request->user();

        $isSuperAdmin = $user->role->value === 'super_admin';

        $isManagement = in_array($user->role->value, [
            'super_admin',
            'supervisor',
        ], true);

        return Inertia::render('Dashboard', [
            'attendanceDashboard' => Inertia::optional(
                fn () => $isManagement
                    ? $attendanceDashboardService->managementDashboard()
                    : $attendanceDashboardService->employeeDashboard(
                        (int) $user->id
                    )
            ),

            'productionDashboard' => $isManagement
                ? Inertia::optional(
                    fn () => $productionDashboardService->getDashboard()
                )
                : null,

            'salaryDashboard' => $isSuperAdmin
                ? Inertia::optional(
                    fn () => $salaryDashboardService->getDashboard()
                )
                : null,

            // Stocks Tab
            'rawMaterialStock' => Inertia::optional(
                fn () => $isManagement ? $stockDashboardService->rawMaterialStock() : null,
            ),

            'toolStock' => Inertia::optional(
                fn () => $isManagement ? $stockDashboardService->toolStock() : null,
            ),
        ]);
    }
}
