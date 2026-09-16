<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\InventoryAdjustRequest;
use App\Http\Requests\Api\V1\Inventory\InventoryStoreRequest;
use App\Http\Requests\Api\V1\Inventory\InventoryUpdateRequest;
use App\Http\Resources\Api\V1\InventoryItemResource;
use App\Http\Resources\Api\V1\InventoryMovementResource;
use App\Http\Responses\ApiResponse;
use App\Models\InventoryItem;
use App\Services\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::with('category');

        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(fn ($q) => $q->where('sku', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $items = $query->latest('id')
            ->paginate(15)
            ->through(fn (InventoryItem $item) => InventoryItemResource::make($item)->resolve($request));

        return ApiResponse::ok($items);
    }

    public function store(InventoryStoreRequest $request, InventoryMovementService $movements): JsonResponse
    {
        $validated = $request->validated();

        $item = DB::transaction(function () use ($validated, $request, $movements) {
            $record = InventoryItem::create(collect($validated)->except('opening_quantity')->all());

            $opening = (int) ($validated['opening_quantity'] ?? 0);

            if ($opening > 0) {
                $movements->record(
                    $record,
                    InventoryMovementType::AdjustmentIn,
                    $opening,
                    $request->user(),
                    'manual',
                    null,
                    'Opening stock',
                );
            }

            return $record->refresh();
        });

        $item->load('category');

        return ApiResponse::created([
            'item' => new InventoryItemResource($item),
        ], 'Inventory item created.');
    }

    public function show(int $item): JsonResponse
    {
        $record = InventoryItem::with('category')->findOrFail($item);

        return ApiResponse::ok([
            'item' => new InventoryItemResource($record),
        ]);
    }

    public function update(InventoryUpdateRequest $request, int $item): JsonResponse
    {
        $record = InventoryItem::findOrFail($item);
        $record->update($request->validated());
        $record->load('category');

        return ApiResponse::ok([
            'item' => new InventoryItemResource($record),
        ], 'Inventory item updated.');
    }

    /**
     * Soft-delete; the movements ledger is kept for audit.
     */
    public function destroy(int $item): JsonResponse
    {
        $record = InventoryItem::findOrFail($item);
        $record->delete();

        return ApiResponse::ok(null, 'Inventory item deleted.');
    }

    /**
     * Scoped movement history for the item.
     */
    public function movements(int $item, Request $request): JsonResponse
    {
        $record = InventoryItem::findOrFail($item);

        $movements = $record->movements()->with('creator')->latest('id')
            ->paginate(15)
            ->through(fn ($movement) => InventoryMovementResource::make($movement)->resolve($request));

        return ApiResponse::ok($movements);
    }

    /**
     * Manual stock adjustment. The movement type is derived from the
     * sign so quantity and ledger stay consistent by construction.
     */
    public function adjust(InventoryAdjustRequest $request, int $item, InventoryMovementService $movements): JsonResponse
    {
        $record = InventoryItem::findOrFail($item);
        $validated = $request->validated();

        $change = (int) $validated['quantity_change'];

        $movement = $movements->record(
            $record,
            $change > 0 ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut,
            $change,
            $request->user(),
            'manual',
            null,
            $validated['note'] ?? null,
        );

        $movement->load('creator');

        return ApiResponse::ok([
            'movement' => new InventoryMovementResource($movement),
            'item' => new InventoryItemResource($movement->item),
        ], 'Stock adjusted.');
    }

    /**
     * Filtered low-stock view: quantity at or below threshold,
     * computed at query time per database-design §3.12.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $items = InventoryItem::with('category')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->latest('id')
            ->paginate(15)
            ->through(fn (InventoryItem $item) => InventoryItemResource::make($item)->resolve($request));

        return ApiResponse::ok($items);
    }
}
