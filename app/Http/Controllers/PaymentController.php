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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     *
     * StorePaymentRequest already rejects an obviously-overpaying request
     * for fast feedback, but that check runs before any lock is held, so
     * it alone cannot prevent two concurrent requests (e.g. a real user
     * double-clicking "record payment") from both passing against the
     * same pre-payment balance. The authoritative check is the re-check
     * below, inside a transaction with the payable row locked — a second
     * concurrent request blocks on the lock until the first transaction
     * commits, then re-reads a balance that already reflects it.
     *
     * On SQLite (used here locally/in tests) there is no true row-level
     * lock; SQLite instead serialises concurrent writers at the whole
     * database level, which happens to give the same net effect for this
     * single-row case. On MySQL/Postgres this uses a real row lock. Either
     * way, the guarantee comes from the transaction + lock, not from the
     * FormRequest's pre-check.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payableClass = $request->resolvePayableClass();

        $payment = DB::transaction(function () use ($request, $payableClass) {
            $payable = $payableClass::query()
                ->whereKey($request->validated('payable_id'))
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $request->validated('amount');
            $balanceDue = $payable->balanceDue();

            if ($amount > $balanceDue) {
                throw ValidationException::withMessages([
                    'amount' => "The payment amount exceeds the remaining balance due ({$balanceDue}).",
                ]);
            }

            $payment = new Payment($request->safe()->only(['amount', 'payment_date', 'method', 'reference', 'notes']));
            $payment->payable()->associate($payable);
            $payment->created_by = $request->user()->id;
            $payment->save();

            return $payment;
        });

        return (new PaymentResource($payment->load('payable')))->response()->setStatusCode(201);
    }
}
