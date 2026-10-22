<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\BulkDestroyRequest;
use App\Http\Requests\RawMaterialTransactions\IndexRawMaterialTransactionRequest;
use App\Http\Requests\RawMaterialTransactions\RawMaterialTransactionRequest;
use App\Http\Resources\RawMaterialTransactionResource;
use App\Models\RawMaterialTransaction;
use App\Services\RawMaterialTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RawMaterialTransactionController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Raw Material Transaction';

    private const string INDEX_ROUTE = 'raw-material-transactions.index';

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(IndexRawMaterialTransactionRequest $request): Response
    {
        Gate::authorize('viewAny', RawMaterialTransaction::class);

        $filters = $request->validated();

        $transactions = RawMaterialTransaction::query()
            ->with(['sectionSize.section', 'user', 'reference'])
            ->filterQuery($filters)
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('RawMaterialTransactions/Index', [
            'transactions' => RawMaterialTransactionResource::collection(
                $transactions,
            ),

            'filters' => [
                'search' => $filters['search'] ?? '',
                'sectionId' => $filters['section_id'] ?? 'all',
                'sectionSizeId' => $filters['section_size_id'] ?? 'all',
                'type' => $filters['type'] ?? 'all',
                'userId' => $filters['user_id'] ?? 'all',
                'startDate' => $filters['start_date'] ?? '',
                'endDate' => $filters['end_date'] ?? '',
                'perPage' => $filters['per_page'] ?? 10,
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function store(
        RawMaterialTransactionRequest $request,
        RawMaterialTransactionService $service,
    ): RedirectResponse {
        $service->create(
            $request->validated(),
            $request->user(),
        );

        return $this->successResponse('created');
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function create(): Response
    {
        Gate::authorize('create', RawMaterialTransaction::class);

        return Inertia::render('RawMaterialTransactions/Create', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(
        RawMaterialTransaction $rawMaterialTransaction,
    ): Response {
        Gate::authorize('view', $rawMaterialTransaction);

        $rawMaterialTransaction->load([
            'sectionSize.section',
            'user',
            'reference',
        ]);

        return Inertia::render('RawMaterialTransactions/Edit', [
            'transaction' => new RawMaterialTransactionResource(
                $rawMaterialTransaction,
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        RawMaterialTransactionRequest $request,
        RawMaterialTransaction $rawMaterialTransaction,
        RawMaterialTransactionService $service,
    ): RedirectResponse {
        Gate::authorize('update', $rawMaterialTransaction);

        $service->update(
            $rawMaterialTransaction,
            $request->validated(),
        );

        return $this->successResponse('updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(
        RawMaterialTransaction $rawMaterialTransaction,
        RawMaterialTransactionService $service,
    ): RedirectResponse {
        Gate::authorize('delete', $rawMaterialTransaction);

        $service->delete($rawMaterialTransaction);

        return $this->successResponse('deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Destroy
    |--------------------------------------------------------------------------
    */

    public function bulkDestroy(
        BulkDestroyRequest $request,
        RawMaterialTransactionService $service,
    ): RedirectResponse {
        Gate::authorize('deleteAny', RawMaterialTransaction::class);

        $service->bulkDelete($request->validated('ids'));

        return $this->successResponse('bulk_deleted');
    }
}
