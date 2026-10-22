<?php

namespace App\Http\Controllers;

use App\Services\Lookup\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __construct(
        private readonly LookupService $lookupService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Enum Lookups
    |--------------------------------------------------------------------------
    */

    public function enum(string $key): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->enum($key),
        ]);
    }

    public function enums(Request $request): JsonResponse
    {
        $keys = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string'],
        ])['keys'];

        return response()->json([
            'data' => $this->lookupService->enums($keys),
        ]);
    }

    public function allEnums(): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->allEnums(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Database Lookups
    |--------------------------------------------------------------------------
    */

    public function parts(): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->parts(),
        ]);
    }

    public function setups(Request $request): JsonResponse
    {
        $partId = $request->input('part_id');

        return response()->json([
            'data' => $this->lookupService->setups($partId),
        ]);
    }

    public function sections(): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->sections(),
        ]);
    }

    public function sectionSizes(int $sectionId): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->sectionSizes($sectionId),
        ]);
    }

    public function machines(): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->machines(),
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $role = $request->input('role');

        return response()->json([
            'data' => $this->lookupService->employees($role),
        ]);
    }

    public function tools(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->lookupService->tools(
                $request->input('type')
            ),
        ]);
    }
}
