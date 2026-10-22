<?php

namespace App\Http\Controllers;

use App\Enums\ToolTransactionType;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HasCrudResponses;
use App\Http\Requests\ToolTransactions\IndexToolTransactionRequest;
use App\Http\Requests\ToolTransactions\ToolTransactionRequest;
use App\Http\Resources\ToolTransactionResource;
use App\Models\Tool;
use App\Models\ToolTransaction;
use App\Models\User;
use App\Services\ToolTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ToolTransactionController extends Controller
{
    use HasCrudResponses;

    private const string MODULE_NAME = 'Tool Transaction';

    private const string INDEX_ROUTE = 'tool-transactions.index';

    public function __construct(
        private readonly ToolTransactionService $toolTransactionService,
    ) {}

    public function index(IndexToolTransactionRequest $request): Response
    {
        Gate::authorize('viewAny', ToolTransaction::class);

        $filters = $request->validated();

        $transactions = ToolTransaction::query()
            ->with([
                'tool',
                'fromUser',
                'toUser',
                'part',
                'setup',
                'createdBy',
            ])
            ->filterQuery($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('ToolTransactions/Index', [
            'transactions' => ToolTransactionResource::collection($transactions),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'toolId' => $filters['tool_id'] ?? null,
                'type' => $filters['type'] ?? null,
                'fromUserId' => $filters['from_user_id'] ?? null,
                'toUserId' => $filters['to_user_id'] ?? null,
                'partId' => $filters['part_id'] ?? null,
                'setupId' => $filters['setup_id'] ?? null,
                'fromDate' => $filters['from_date'] ?? null,
                'toDate' => $filters['to_date'] ?? null,
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'perPage' => $filters['per_page'] ?? 10,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ToolTransaction::class);

        return Inertia::render('ToolTransactions/Create', []);
    }

    public function store(ToolTransactionRequest $request): RedirectResponse
    {
        Gate::authorize('create', ToolTransaction::class);

        $user = Auth::user();

        $data = $request->validated();
        $tool = Tool::query()->findOrFail($data['tool_id']);
        $type = ToolTransactionType::from($data['type']);
        $quantity = (int) $data['quantity'];

        match ($type) {
            ToolTransactionType::OPENING => $this->toolTransactionService->opening(
                tool: $tool,
                quantity: $quantity,
                user: $user,
                remarks: $data['remarks'] ?? null,
            ),

            ToolTransactionType::PURCHASE => $this->toolTransactionService->purchase(
                tool: $tool,
                quantity: $quantity,
                user: $user,
                remarks: $data['remarks'] ?? null,
            ),

            ToolTransactionType::ISSUE => $this->toolTransactionService->issue(
                tool: $tool,
                fromUser: isset($data['from_user_id'])
                    ? User::query()->findOrFail($data['from_user_id'])
                    : null,
                toUser: User::query()->findOrFail($data['to_user_id']),
                quantity: $quantity,
                createdBy: $user,
                partId: $data['part_id'] ?? null,
                setupId: $data['setup_id'] ?? null,
                returnScrap: $data['return_scrap'] ?? null,
                remarks: $data['remarks'] ?? null,
            ),

            ToolTransactionType::ADJUSTMENT => $this->toolTransactionService->adjustment(
                tool: $tool,
                quantity: $quantity,
                createdBy: $user,
                remarks: $data['remarks'] ?? null,
            ),
        };

        return $this->successResponse('created');
    }

    public function edit(ToolTransaction $toolTransaction): Response
    {
        Gate::authorize('update', $toolTransaction);

        $toolTransaction->load([
            'tool',
            'fromUser',
            'toUser',
            'part',
            'setup',
            'createdBy',
        ]);

        return Inertia::render('ToolTransactions/Edit', [
            'transaction' => new ToolTransactionResource($toolTransaction),
        ]);
    }

    public function update(
        ToolTransactionRequest $request,
        ToolTransaction $toolTransaction,
    ): RedirectResponse {
        Gate::authorize('update', $toolTransaction);

        /** @var User $user */
        $user = Auth::user();

        $this->toolTransactionService->update(
            transaction: $toolTransaction,
            data: $request->validated(),
            user: $user,
        );

        return $this->successResponse('updated');
    }

    public function destroy(ToolTransaction $toolTransaction): RedirectResponse
    {
        Gate::authorize('delete', $toolTransaction);

        /** @var User $user */
        $user = Auth::user();

        $this->toolTransactionService->delete(
            transaction: $toolTransaction,
            user: $user,
        );

        return $this->successResponse('deleted');
    }

    public function bulkDestroy(IndexToolTransactionRequest $request): RedirectResponse
    {
        Gate::authorize('deleteAny', ToolTransaction::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $query = ToolTransaction::query()
            ->whereIn('id', $validated['ids']);

        if ($user->role !== UserRole::SUPER_ADMIN) {
            $query->where('created_by_user_id', $user->id);
        }

        $transactions = $query->get();

        if ($transactions->count() !== count($validated['ids'])) {
            throw ValidationException::withMessages([
                'ids' => 'One or more transactions were not found or you are not authorized to delete them.',
            ]);
        }

        foreach ($transactions as $transaction) {
            Gate::authorize('delete', $transaction);
        }

        $this->toolTransactionService->bulkDelete(
            transactions: $transactions,
            user: $user,
        );

        return $this->successResponse('bulk_deleted');
    }
}
