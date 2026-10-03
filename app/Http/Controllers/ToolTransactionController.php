<?php

namespace App\Http\Controllers;

use App\Enums\ToolTransactionType;
use App\Helpers\ResponseHelper;
use App\Http\Requests\ToolTransactions\IndexToolTransactionRequest;
use App\Http\Requests\ToolTransactions\ToolTransactionRequest;
use App\Http\Resources\ToolTransactionResource;
use App\Models\Tool;
use App\Models\ToolTransaction;
use App\Models\User;
use App\Services\ToolTransactionService;
use App\Support\FlashMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ToolTransactionController extends Controller
{
    private const string MODULE_NAME = 'Tool Transaction';

    public function __construct(
        private readonly ToolTransactionService $toolTransactionService,
    ) {}

    public function index(
        IndexToolTransactionRequest $request,
    ): Response {
        Gate::authorize('viewAny', ToolTransaction::class);

        $validated = $request->validated();

        $query = ToolTransaction::query()
            ->with([
                'tool',
                'fromUser',
                'toUser',
                'part',
                'setup',
                'createdBy',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query
                    ->whereHas('tool', function ($query) use ($search) {
                        $query->where(
                            'name',
                            'like',
                            "%{$search}%",
                        );
                    })
                    ->orWhereHas('fromUser', function ($query) use ($search) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$search}%",
                            )
                            ->orWhere(
                                'employee_code',
                                'like',
                                "%{$search}%",
                            );
                    })
                    ->orWhereHas('toUser', function ($query) use ($search) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$search}%",
                            )
                            ->orWhere(
                                'employee_code',
                                'like',
                                "%{$search}%",
                            );
                    })
                    ->orWhereHas('part', function ($query) use ($search) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$search}%",
                            )
                            ->orWhere(
                                'reference_name',
                                'like',
                                "%{$search}%",
                            );
                    })
                    ->orWhere(
                        'remarks',
                        'like',
                        "%{$search}%",
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $query
            ->when(
                $validated['tool_id'] ?? null,
                fn ($query, $toolId) => $query->where(
                    'tool_id',
                    $toolId,
                ),
            )
            ->when(
                $validated['type'] ?? null,
                fn ($query, $type) => $query->where(
                    'type',
                    $type,
                ),
            )
            ->when(
                $validated['from_user_id'] ?? null,
                fn ($query, $userId) => $query->where(
                    'from_user_id',
                    $userId,
                ),
            )
            ->when(
                $validated['to_user_id'] ?? null,
                fn ($query, $userId) => $query->where(
                    'to_user_id',
                    $userId,
                ),
            )
            ->when(
                $validated['part_id'] ?? null,
                fn ($query, $partId) => $query->where(
                    'part_id',
                    $partId,
                ),
            )
            ->when(
                $validated['setup_id'] ?? null,
                fn ($query, $setupId) => $query->where(
                    'setup_id',
                    $setupId,
                )
            )
            ->when(
                $validated['from_date'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'created_at',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $validated['to_date'] ?? null,
                fn ($query, $date) => $query->whereDate(
                    'created_at',
                    '<=',
                    $date,
                ),
            );

        /*
        |--------------------------------------------------------------------------
        | Sorting & Pagination
        |--------------------------------------------------------------------------
        */

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 15;

        $transactions = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render(
            'ToolTransactions/Index',
            [
                'transactions' => ToolTransactionResource::collection(
                    $transactions,
                ),

                'filters' => [
                    'search' => $validated['search'] ?? '',
                    'toolId' => $validated['tool_id'] ?? null,
                    'type' => $validated['type'] ?? null,
                    'fromUserId' => $validated['from_user_id'] ?? null,
                    'toUserId' => $validated['to_user_id'] ?? null,
                    'partId' => $validated['part_id'] ?? null,
                    'setupId' => $validated['setup_id'] ?? null,
                    'fromDate' => $validated['from_date'] ?? null,
                    'toDate' => $validated['to_date'] ?? null,
                    'sort' => $validated['sort'] ?? 'created_at',
                    'direction' => $validated['direction'] ?? 'desc',
                ],
            ],
        );
    }

    public function create(): Response
    {
        Gate::authorize('create', ToolTransaction::class);

        return Inertia::render(
            'ToolTransactions/Create',
        );
    }

    public function store(
        ToolTransactionRequest $request,
    ): RedirectResponse {
        Gate::authorize('create', ToolTransaction::class);

        $validated = $request->validated();
        $user = Auth::user();

        $transaction = ToolTransactionType::from(
            $validated['type'],
        );

        match ($transaction) {
            ToolTransactionType::OPENING =>
            $this->toolTransactionService->opening(
                tool: Tool::findOrFail(
                    $validated['tool_id'],
                ),
                quantity: (int) $validated['quantity'],
                user: $user,
                remarks: $validated['remarks'] ?? null,
            ),

            ToolTransactionType::PURCHASE =>
            $this->toolTransactionService->purchase(
                tool: Tool::findOrFail(
                    $validated['tool_id'],
                ),
                quantity: (int) $validated['quantity'],
                user: $user,
                remarks: $validated['remarks'] ?? null,
            ),

            ToolTransactionType::ISSUE =>
            $this->toolTransactionService->issue(
                tool: Tool::findOrFail(
                    $validated['tool_id'],
                ),
                fromUser: User::findOrFail(
                    $validated['from_user_id'],
                ),
                toUser: User::findOrFail(
                    $validated['to_user_id'],
                ),
                quantity: (int) $validated['quantity'],
                createdBy: $user,
                partId: $validated['part_id'] ?? null,
                setupId: $validated['setup_id'] ?? null,
                remarks: $validated['remarks'] ?? null,
            ),

            ToolTransactionType::RETURN =>
            $this->toolTransactionService->return(
                tool: Tool::findOrFail(
                    $validated['tool_id'],
                ),
                fromUser: User::findOrFail(
                    $validated['from_user_id'],
                ),
                quantity: (int) $validated['quantity'],
                createdBy: $user,
                partId: $validated['part_id'] ?? null,
                setupId: $validated['setup_id'] ?? null,
                returnScrap: $validated['return_scrap'] ?? null,
                remarks: $validated['remarks'] ?? null,
            ),

            ToolTransactionType::ADJUSTMENT =>
            $this->toolTransactionService->adjustment(
                tool: Tool::findOrFail(
                    $validated['tool_id'],
                ),
                quantity: (int) $validated['quantity'],
                createdBy: $user,
                remarks: $validated['remarks'] ?? null,
            ),
        };

        return $this->successResponse('created');
    }

    public function edit(
        ToolTransaction $toolTransaction,
    ): Response {
        Gate::authorize('update', $toolTransaction);

        $toolTransaction->load([
            'tool',
            'fromUser',
            'toUser',
            'part',
            'setup',
        ]);

        return Inertia::render(
            'ToolTransactions/Edit',
            [
                'transaction' => new ToolTransactionResource(
                    $toolTransaction,
                ),
            ],
        );
    }

    public function update(
        ToolTransactionRequest $request,
        ToolTransaction $toolTransaction,
    ): RedirectResponse {
        Gate::authorize('update', $toolTransaction);

        $this->toolTransactionService->update(
            transaction: $toolTransaction,
            data: $request->validated(),
            user: Auth::user(),
        );

        return $this->successResponse('updated');
    }

    public function destroy(
        ToolTransaction $toolTransaction,
    ): RedirectResponse {
        Gate::authorize('delete', $toolTransaction);

        $this->toolTransactionService->delete(
            transaction: $toolTransaction,
            user: Auth::user(),
        );

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(
        IndexToolTransactionRequest $request,
    ): RedirectResponse {
        Gate::authorize(
            'deleteAny',
            ToolTransaction::class,
        );

        $ids = $request->validate([
            'ids' => [
                'required',
                'array',
                'min:1',
            ],
            'ids.*' => [
                'required',
                'integer',
            ],
        ])['ids'];

        $transactions = ToolTransaction::query()
            ->whereIn('id', $ids)
            ->get();

        $this->toolTransactionService->bulkDelete(
            transactions: $transactions,
            user: Auth::user(),
        );

        return $this->successResponse('bulk_deleted');
    }

    private function successResponse(
        string $action,
    ): RedirectResponse {
        return ResponseHelper::success(
            message: FlashMessage::success(
                $action,
                self::MODULE_NAME,
            ),
            route: 'tool-transactions.index',
        );
    }
}
