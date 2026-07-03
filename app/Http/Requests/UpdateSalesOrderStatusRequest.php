<?php

namespace App\Http\Requests;

use App\Models\SalesOrder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var SalesOrder $salesOrder */
        $salesOrder = $this->route('sales_order');

        return $this->user()?->can('update', $salesOrder) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * This is a fast, non-authoritative check for clear feedback in the
     * common case — it runs before any lock is held, so it cannot alone
     * prevent a race against a concurrent payment being recorded. The
     * authoritative re-check lives in UpdateSalesOrderStatus, inside a
     * transaction with the sales order row locked.
     *
     * @return array<string, ValidationRule|array<mixed>|string|Closure>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(SalesOrder::statuses()),
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var SalesOrder $salesOrder */
                    $salesOrder = $this->route('sales_order');

                    if (! $salesOrder->canTransitionTo($value)) {
                        $fail("Cannot move a sales order from \"{$salesOrder->status}\" to \"{$value}\".");

                        return;
                    }

                    if ($value === SalesOrder::STATUS_CANCELLED && $salesOrder->payments()->exists()) {
                        $fail('Cannot cancel a sales order that already has recorded payments.');
                    }
                },
            ],
        ];
    }
}
