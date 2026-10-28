<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\Machines\IndexMachineRequest;
use App\Http\Requests\Machines\MachineRequest;
use App\Http\Resources\MachineResource;
use App\Imports\MachinesImport;
use App\Models\Machine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class MachineController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Machine';

    private const string INDEX_ROUTE = 'machines.index';

    public function index(IndexMachineRequest $request): Response
    {
        Gate::authorize('viewAny', Machine::class);

        $filters = $request->validated();

        $machines = Machine::query()
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Machines/Index', [
            'machines' => MachineResource::collection($machines),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'id',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    public function store(MachineRequest $request): RedirectResponse
    {
        Gate::authorize('create', Machine::class);

        Machine::create($request->validated());

        return $this->successResponse('created');
    }

    public function create(): Response
    {
        Gate::authorize('create', Machine::class);

        return Inertia::render('Machines/Create', []);
    }

    public function edit(Machine $machine): Response
    {
        Gate::authorize('view', $machine);

        return Inertia::render('Machines/Edit', [
            'machine' => new MachineResource($machine),
        ]);
    }

    public function update(
        MachineRequest $request,
        Machine $machine,
    ): RedirectResponse {
        Gate::authorize('update', $machine);

        $machine->update($request->validated());

        return $this->successResponse('updated');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        Gate::authorize('delete', $machine);

        $machine->delete();

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        BulkDestroyRequest $request,
    ): RedirectResponse {
        Gate::authorize('deleteAny', Machine::class);

        Machine::query()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->successResponse('bulk_deleted');
    }

    public function import()
    {
        return Inertia::render('Machines/Import', []);
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

        $import = new MachinesImport;

        $file = $request->file('file');

        $file = $request->file('file');

        dd([
            'php_version' => PHP_VERSION,
            'has_file' => $request->hasFile('file'),
            'file_class' => $file ? get_class($file) : null,
            'original_name' => $file?->getClientOriginalName(),
            'upload_error_code' => $file?->getError(),
            'upload_error_message' => $file?->getErrorMessage(),
            'temporary_path' => $file?->getPathname(),
            'real_path' => $file?->getRealPath(),
            'is_valid' => $file?->isValid(),
            'temp_directory_exists' => is_dir(sys_get_temp_dir()),
            'temp_directory_writable' => is_writable(sys_get_temp_dir()),
        ]);
        if (! $file || ! $file->isValid()) {
            throw new \RuntimeException(
                'Excel upload failed: ' .
                ($file?->getErrorMessage() ?? 'No uploaded file received.')
            );
        }

        if (! $file->getRealPath()) {
            throw new \RuntimeException(
                'Excel upload has no valid temporary file path.'
            );
        }

        dd([
            'php_version' => PHP_VERSION,
            'file_exists' => $file ? file_exists($file->getPathname()) : false,
            'is_valid' => $file?->isValid(),
            'upload_error' => $file?->getError(),
            'temporary_path' => $file?->getPathname(),
            'real_path' => $file?->getRealPath(),
            'file_size' => $file?->getSize(),
        ]);

        Excel::import($import, $request->file('file'));

        return $this->successResponse(
            action: 'imported_with_counts',
            created: $import->getCreatedCount(),
            updated: $import->getUpdatedCount(),
        );
    }
}
