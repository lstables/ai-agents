<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InventoryItemController extends Controller
{
    /**
     * List inventory items with server-side pagination, filtering, and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryItem::class);

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $items = InventoryItem::query()
            ->with('supplier')
            ->when($request->boolean('below_reorder_level'), function ($query) {
                $query->belowReorderLevel();
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return InventoryItemResource::collection($items);
    }

    /**
     * Create a new inventory item.
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = InventoryItem::create($request->validated());

        return (new InventoryItemResource($item->load('supplier')))->response()->setStatusCode(201);
    }

    /**
     * Update an existing inventory item.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem->update($request->validated());

        return new InventoryItemResource($inventoryItem->load('supplier'));
    }

    /**
     * Delete an inventory item. Nothing else in this codebase references
     * InventoryItem yet, so no data-integrity guard is needed here today
     * (see .ai/tasks/issue-005-inventory-module.md's Blocked Questions).
     */
    public function destroy(InventoryItem $inventoryItem): Response
    {
        $this->authorize('delete', $inventoryItem);

        $inventoryItem->delete();

        return response()->noContent();
    }
}
