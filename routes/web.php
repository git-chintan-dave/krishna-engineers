<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BandsawWorkLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\OperatorWorkLogController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PartSetupController;
use App\Http\Controllers\RawMaterialTransactionController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SectionSizeController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\ToolTransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardController::class)
        ->middleware('auth')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */

    Route::prefix('master')->group(function () {
        // Machines
        Route::delete(
            'machines/bulk-delete',
            [MachineController::class, 'bulkDestroy'],
        )->name('machines.bulk-destroy');

        Route::resource(
            'machines',
            MachineController::class,
        )->except(['show']);

        // Parts
        Route::delete(
            'parts/bulk-delete',
            [PartController::class, 'bulkDestroy'],
        )->name('parts.bulk-destroy');

        Route::resource(
            'parts',
            PartController::class,
        )->except(['show']);

        // Setups
        Route::delete(
            'setups/bulk-delete',
            [SetupController::class, 'bulkDestroy'],
        )->name('setups.bulk-destroy');

        Route::resource(
            'setups',
            SetupController::class,
        )->except(['show']);

        // Part Setups
        Route::delete(
            'part-setups/bulk-delete',
            [PartSetupController::class, 'bulkDestroy'],
        )->name('part-setups.bulk-destroy');

        Route::resource(
            'part-setups',
            PartSetupController::class,
        )->except(['show']);

        // Sections
        Route::delete(
            'sections/bulk-delete',
            [SectionController::class, 'bulkDestroy'],
        )->name('sections.bulk-destroy');

        Route::resource(
            'sections',
            SectionController::class,
        )->except(['show']);

        // Section Sizes
        Route::delete(
            'section-sizes/bulk-delete',
            [SectionSizeController::class, 'bulkDestroy'],
        )->name('section-sizes.bulk-destroy');

        Route::resource(
            'section-sizes',
            SectionSizeController::class,
        )->except(['show']);

        // Tools
        Route::delete(
            'tools/bulk-delete',
            [ToolController::class, 'bulkDestroy'],
        )->name('tools.bulk-destroy');

        Route::resource(
            'tools',
            ToolController::class,
        )->except(['show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    Route::prefix('inventory')->group(function () {
        // Raw Material Transactions
        Route::delete(
            'raw-material-transactions/bulk-delete',
            [RawMaterialTransactionController::class, 'bulkDestroy'],
        )->name('raw-material-transactions.bulk-destroy');

        Route::resource(
            'raw-material-transactions',
            RawMaterialTransactionController::class,
        )->except(['show']);

        // Tool Transactions
        Route::delete(
            'tool-transactions/bulk-delete',
            [ToolTransactionController::class, 'bulkDestroy'],
        )->name('tool-transactions.bulk-destroy');

        Route::resource(
            'tool-transactions',
            ToolTransactionController::class,
        )->except(['show']);
    });

    /*
    |--------------------------------------------------------------------------
    | People
    |--------------------------------------------------------------------------
    */

    Route::prefix('people')->group(function () {
        // Employees
        Route::delete(
            'employees/bulk-delete',
            [EmployeeController::class, 'bulkDestroy'],
        )->name('employees.bulk-destroy');

        Route::resource(
            'employees',
            EmployeeController::class,
        )->except(['show']);

        // Attendances
        Route::get(
            'attendances/import',
            [AttendanceController::class, 'importView'],
        )->name('attendances.import.view');

        Route::post(
            'attendances/import',
            [AttendanceController::class, 'import'],
        )->name('attendances.import.store');

        Route::post(
            'attendances/sync',
            [AttendanceController::class, 'sync'],
        )->name('attendances.sync');

        Route::delete(
            'attendances/bulk-delete',
            [AttendanceController::class, 'bulkDestroy'],
        )->name('attendances.bulk-destroy');

        Route::resource(
            'attendances',
            AttendanceController::class,
        )->except(['show']);

        // Salaries
        Route::delete(
            'salaries/bulk-delete',
            [SalaryController::class, 'bulkDestroy'],
        )->name('salaries.bulk-destroy');

        Route::get(
            'salaries/preview',
            [SalaryController::class, 'preview'],
        )->name('salaries.preview');

        Route::post(
            'salaries/{salary}/mark-as-paid',
            [SalaryController::class, 'markAsPaid'],
        )->name('salaries.mark-as-paid');

        Route::post(
            'salaries/generate-last-month',
            [SalaryController::class, 'generateLastMonth']
        )->name('salaries.generate-last-month');

        Route::resource(
            'salaries',
            SalaryController::class,
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Work logs
    |--------------------------------------------------------------------------
    */

    Route::prefix('work-logs')->group(function () {
        // Operator
        Route::delete(
            'operator/bulk-delete',
            [OperatorWorkLogController::class, 'bulkDestroy'],
        )->name('operator.bulk-destroy');

        Route::resource(
            'operator',
            OperatorWorkLogController::class,
        )->except(['show']);

        // Bandsaw
        Route::delete(
            'bandsaw/bulk-delete',
            [BandsawWorkLogController::class, 'bulkDestroy'],
        )->name('bandsaw.bulk-destroy');

        Route::resource(
            'bandsaw',
            BandsawWorkLogController::class,
        )->except(['show']);
    });

    // Lookup
    Route::prefix('lookups')
        ->name('lookups.')
        ->controller(LookupController::class)
        ->group(function () {
            // Enums
            Route::get('/enums', 'allEnums')
                ->name('enums.all');

            Route::get('/enums/{key}', 'enum')
                ->name('enums.single');

            Route::post('/enums', 'enums')
                ->name('enums.multiple');

            // Database
            Route::get('/parts', 'parts')
                ->name('parts');

            Route::get('/setups', 'setups')
                ->name('setups');

            Route::get('/sections', 'sections')
                ->name('sections');

            Route::get('/sections/{sectionId}/sizes', 'sectionSizes')
                ->name('sections.sizes');

            Route::get('/machines', 'machines')
                ->name('machines');

            Route::get('/employees', 'employees')
                ->name('employees');

            Route::get('/tools', 'tools')
                ->name('tools');
        });

});

require __DIR__.'/settings.php';
