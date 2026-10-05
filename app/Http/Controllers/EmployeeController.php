<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Queries\Concerns\AppliesIndexFilters;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Users\IndexUserRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use AppliesIndexFilters, HasCrudResponses;

    private const string MODULE_NAME = 'Employee';

    private const string INDEX_ROUTE = 'employees.index';

    // Index
    public function index(
        IndexUserRequest $request,
    ): Response {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validated();

        $employees = $this->employeeQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Employees/Index', [
            'employees' => UserResource::collection($employees),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? 'all',
                'shift' => $filters['shift'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    // Create
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Employees/Create', []);
    }

    // Store
    public function store(
        UserRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', User::class);

        User::create($request->validated());

        return $this->successResponse('created');
    }

    // Edit
    public function edit(
        User $employee,
    ): Response {
        Gate::authorize('view', $employee);

        return Inertia::render('Employees/Edit', [
            'employee' => new UserResource($employee),
        ]);
    }

    // Update
    public function update(
        UserRequest $request,
        User $employee,
    ): RedirectResponse {
        Gate::authorize('update', $employee);

        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $employee->update($data);

        return $this->successResponse('updated');
    }

    // Destroy
    public function destroy(
        User $employee,
    ): RedirectResponse {
        Gate::authorize('delete', $employee);

        $employee->delete();

        return $this->successResponse('deleted');
    }

    // Bulk Destroy
    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', User::class);

        User::query()
            ->whereIn('id', $request->validated('ids'))
            ->where('role', '!=', UserRole::SUPER_ADMIN->value)
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    // Helpers
    private function employeeQuery(array $filters): Builder
    {
        return $this->applyIndexFilters(
            User::query(),
            $filters,
            [
                'name',
                'employee_code',
            ],
        )
            ->when(
                $filters['role'] ?? null,
                fn (Builder $query, string $role): Builder => $query->where('role', $role),
            )
            ->when(
                $filters['shift'] ?? null,
                fn (Builder $query, string $shift): Builder => $query->where('shift', $shift),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            );
    }
}
