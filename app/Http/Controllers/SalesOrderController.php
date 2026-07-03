<?php

namespace App\Http\Controllers;

use App\Actions\SalesOrders\CreateSalesOrder;
use App\Actions\SalesOrders\UpdateSalesOrderStatus;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderStatusRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderController extends Controller
{
    /**
     * List sales orders with server-side pagination, filtering, and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $salesOrders = SalesOrder::query()
            ->with('customer')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', $request->integer('customer_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return SalesOrderResource::collection($salesOrders);
    }

    /**
     * Create a new sales order with its line items.
     */
    public function store(StoreSalesOrderRequest $request, CreateSalesOrder $createSalesOrder): JsonResponse
    {
        $salesOrder = $createSalesOrder->handle($request->validated(), $request->user());

        return (new SalesOrderResource($salesOrder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Move a sales order to a new status. Authorization uses the same
     * `update` policy method already defined on SalesOrderPolicy.
     */
    public function updateStatus(UpdateSalesOrderStatusRequest $request, SalesOrder $salesOrder, UpdateSalesOrderStatus $updateSalesOrderStatus): SalesOrderResource
    {
        $updated = $updateSalesOrderStatus->handle($salesOrder, $request->validated('status'));

        return new SalesOrderResource($updated);
    }
}
