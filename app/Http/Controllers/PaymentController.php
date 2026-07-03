<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /**
     * List payments with server-side pagination, filtering, and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $payments = Payment::query()
            ->with('payable')
            ->when($request->filled('payable_type'), function ($query) use ($request) {
                $class = Relation::getMorphedModel((string) $request->string('payable_type'));

                if ($class) {
                    $query->where('payable_type', $request->string('payable_type'));
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHasMorph('payable', [Purchase::class, SalesOrder::class], function ($query) use ($search) {
                            $query->where('reference', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return PaymentResource::collection($payments);
    }

    /**
     * Record a new payment against a purchase or sales order.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payable = $request->resolvePayable();

        $payment = new Payment($request->safe()->only(['amount', 'payment_date', 'method', 'reference', 'notes']));
        $payment->payable()->associate($payable);
        $payment->created_by = $request->user()->id;
        $payment->save();

        return (new PaymentResource($payment->load('payable')))->response()->setStatusCode(201);
    }
}
