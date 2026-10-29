<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Controllers\Concerns\HasListingFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Users\IndexUserRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\UserResource;
use App\Imports\EmployeesImport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    use HasCrudResponses, HasListingFilters;

    private const string MODULE_NAME = 'Employee';

    private const string INDEX_ROUTE = 'employees.index';

    // Index
    public function index(IndexUserRequest $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validated();

        return Inertia::render('Employees/Index', [
            'employees' => Inertia::defer(function () use ($filters) {
                $employees = User::query()
                    ->filterQuery($filters)
                    ->paginate($filters['per_page'] ?? 10)
                    ->withQueryString();

                return UserResource::collection($employees);
            }),

            'statistics' => Inertia::defer(
                fn () => $this->getStatistics(),
            ),

            'hasAnyRecords' => User::query()->exists(),

            'filters' => [
                ...$this->listingFilters($filters),
                'role' => $filters['role'] ?? 'all',
                'shift' => $filters['shift'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
            ],
        ]);
    }

    // Create

    public function store(
        UserRequest $request,
    ): RedirectResponse
    {
        Gate::authorize('create', User::class);

        User::create($request->validated());

        return $this->successResponse('created');
    }

    // Store

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Employees/Create', []);
    }

    // Edit

    public function edit(User $employee): Response
    {
        Gate::authorize('view', $employee);

        return Inertia::render('Employees/Edit', [
            'employee' => new UserResource($employee),
        ]);
    }

    // Update
    public function update(UserRequest $request, User $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $employee->update($data);

        return $this->successResponse('updated');
    }

    // Destroy
    public function destroy(User $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $employee->delete();

        return $this->successResponse('deleted');
    }

    // Bulk Destroy
    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        Gate::authorize('deleteAny', User::class);

        User::query()
            ->whereIn('id', $request->validated('ids'))
            ->where('role', '!=', UserRole::SUPER_ADMIN->value)
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        $import = new EmployeesImport;

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }

    public function import()
    {
        return Inertia::render('Employees/Import', []);
    }

    private function getStatistics(): array
    {
        $stats = User::query()
            ->selectRaw('COUNT(*) AS total')

            // Status statistics
            ->selectRaw(
                "SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active",
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive",
            )

            // Role statistics
            ->selectRaw(
                "SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS super_admin",
            )
            ->selectRaw(
                "SUM(CASE WHEN role = 'supervisor' THEN 1 ELSE 0 END) AS supervisor",
            )
            ->selectRaw(
                "SUM(CASE WHEN role = 'operator' THEN 1 ELSE 0 END) AS operator",
            )
            ->selectRaw(
                "SUM(CASE WHEN role = 'bandsaw' THEN 1 ELSE 0 END) AS bandsaw",
            )
            ->selectRaw(
                "SUM(CASE WHEN role = 'deburring_packaging' THEN 1 ELSE 0 END) AS deburring_packaging",
            )

            // Shift statistics
            ->selectRaw(
                "SUM(CASE WHEN shift = 'day' THEN 1 ELSE 0 END) AS day",
            )
            ->selectRaw(
                "SUM(CASE WHEN shift = 'night' THEN 1 ELSE 0 END) AS night",
            )
            ->selectRaw(
                "SUM(CASE WHEN shift = '8_hr' THEN 1 ELSE 0 END) AS eight_hr",
            )
            ->selectRaw(
                "SUM(CASE WHEN shift = '8_hr_total' THEN 1 ELSE 0 END) AS eight_hr_total",
            )
            ->firstOrFail();

        return [
            'total' => (int) $stats->total,

            'active' => (int) $stats->active,
            'inactive' => (int) $stats->inactive,

            'super_admin' => (int) $stats->super_admin,
            'supervisor' => (int) $stats->supervisor,
            'operator' => (int) $stats->operator,
            'bandsaw' => (int) $stats->bandsaw,
            'deburring_packaging' => (int) $stats->deburring_packaging,

            'day' => (int) $stats->day,
            'night' => (int) $stats->night,
            '8_hr' => (int) $stats->eight_hr,
            '8_hr_total' => (int) $stats->eight_hr_total,
        ];
    }
}
