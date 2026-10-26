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

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::inertia('/', 'Welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */

    Route::prefix('master')->group(function () {
        /*
        | Machines
        */

        Route::controller(MachineController::class)
            ->prefix('machines')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('machines.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('machines.import.view');

                Route::post('/import', 'importStore')
                    ->name('machines.import.store');
            });

        Route::resource('machines', MachineController::class)
            ->except(['show']);

        /*
        | Parts
        */

        Route::controller(PartController::class)
            ->prefix('parts')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('parts.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('parts.import.view');

                Route::post('/import', 'importStore')
                    ->name('parts.import.store');
            });

        Route::resource('parts', PartController::class)
            ->except(['show']);

        /*
        | Setups
        */

        Route::controller(SetupController::class)
            ->prefix('setups')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('setups.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('setups.import.view');

                Route::post('/import', 'importStore')
                    ->name('setups.import.store');
            });

        Route::resource('setups', SetupController::class)
            ->except(['show']);

        /*
        | Part Setups
        */

        Route::controller(PartSetupController::class)
            ->prefix('part-setups')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('part-setups.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('part-setups.import.view');

                Route::post('/import', 'importStore')
                    ->name('part-setups.import.store');
            });

        Route::resource('part-setups', PartSetupController::class)
            ->except(['show']);

        /*
        | Sections
        */

        Route::controller(SectionController::class)
            ->prefix('sections')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('sections.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('sections.import.view');

                Route::post('/import', 'importStore')
                    ->name('sections.import.store');
            });

        Route::resource('sections', SectionController::class)
            ->except(['show']);

        /*
        | Section Sizes
        */

        Route::controller(SectionSizeController::class)
            ->prefix('section-sizes')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('section-sizes.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('section-sizes.import.view');

                Route::post('/import', 'importStore')
                    ->name('section-sizes.import.store');
            });

        Route::resource('section-sizes', SectionSizeController::class)
            ->except(['show']);

        /*
        | Tools
        */

        Route::controller(ToolController::class)
            ->prefix('tools')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('tools.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('tools.import.view');

                Route::post('/import', 'importStore')
                    ->name('tools.import.store');
            });

        Route::resource('tools', ToolController::class)
            ->except(['show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    Route::prefix('inventory')->group(function () {
        /*
        | Raw Material Transactions
        */

        Route::delete(
            'raw-material-transactions/bulk-delete',
            [RawMaterialTransactionController::class, 'bulkDestroy'],
        )->name('raw-material-transactions.bulk-destroy');

        Route::resource(
            'raw-material-transactions',
            RawMaterialTransactionController::class,
        )->except(['show']);

        /*
        | Tool Transactions
        */

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
        /*
        | Employees
        */

        Route::controller(EmployeeController::class)
            ->prefix('employees')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('employees.bulk-destroy');

                Route::get('/import', 'import')
                    ->name('employees.import.view');

                Route::post('/import', 'importStore')
                    ->name('employees.import.store');
            });

        Route::resource('employees', EmployeeController::class)
            ->except(['show']);

        /*
        | Attendances
        */

        Route::controller(AttendanceController::class)
            ->prefix('attendances')
            ->group(function () {
                Route::get('/import', 'importView')
                    ->name('attendances.import.view');

                Route::post('/import', 'import')
                    ->name('attendances.import.store');

                Route::post('/sync', 'sync')
                    ->name('attendances.sync');

                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('attendances.bulk-destroy');

                Route::get('/history', 'history')
                    ->name('attendances.history');
            });

        Route::resource('attendances', AttendanceController::class)
            ->except(['show']);

        /*
        | Salaries
        */

        Route::controller(SalaryController::class)
            ->prefix('salaries')
            ->group(function () {
                Route::delete('/bulk-delete', 'bulkDestroy')
                    ->name('salaries.bulk-destroy');

                Route::get('/preview', 'preview')
                    ->name('salaries.preview');

                Route::post('/{salary}/mark-as-paid', 'markAsPaid')
                    ->name('salaries.mark-as-paid');

                Route::post('/generate-last-month', 'generateSalary')
                    ->name('salaries.generate-last-month');

                Route::post('/{salary}/regenerate', 'regenerate')
                    ->name('salaries.regenerate');
            });

        Route::resource('salaries', SalaryController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Work Logs
    |--------------------------------------------------------------------------
    */

    Route::prefix('work-logs')->group(function () {
        /*
        | Operator Work Logs
        */

        Route::delete(
            'operator/bulk-delete',
            [OperatorWorkLogController::class, 'bulkDestroy'],
        )->name('operator.bulk-destroy');

        Route::resource(
            'operator',
            OperatorWorkLogController::class,
        )->except(['show']);

        /*
        | Bandsaw Work Logs
        */

        Route::delete(
            'bandsaw/bulk-delete',
            [BandsawWorkLogController::class, 'bulkDestroy'],
        )->name('bandsaw.bulk-destroy');

        Route::resource(
            'bandsaw',
            BandsawWorkLogController::class,
        )->except(['show']);
    });

    /*
    |--------------------------------------------------------------------------
    | Lookups
    |--------------------------------------------------------------------------
    */

    Route::prefix('lookups')
        ->name('lookups.')
        ->controller(LookupController::class)
        ->group(function () {
            /*
            | Enums
            */

            Route::get('/enums', 'allEnums')
                ->name('enums.all');

            Route::get('/enums/{key}', 'enum')
                ->name('enums.single');

            Route::post('/enums', 'enums')
                ->name('enums.multiple');

            /*
            | Master Data Lookups
            */

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

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
