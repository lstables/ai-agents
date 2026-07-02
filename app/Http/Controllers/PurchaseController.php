<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\CreatePurchase;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseController extends Controller
{
    /**
     * List purchases with server-side pagination, filtering, and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Purchase::class);

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $purchases = Purchase::query()
            ->with('supplier')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->integer('supplier_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return PurchaseResource::collection($purchases);
    }

    /**
     * Create a new purchase with its line items.
     */
    public function store(StorePurchaseRequest $request, CreatePurchase $createPurchase): JsonResponse
    {
        $purchase = $createPurchase->handle($request->validated(), $request->user());

        return (new PurchaseResource($purchase))
            ->response()
            ->setStatusCode(201);
    }
}
