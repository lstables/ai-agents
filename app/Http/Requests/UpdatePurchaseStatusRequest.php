<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Purchase $purchase */
        $purchase = $this->route('purchase');

        return $this->user()?->can('update', $purchase) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * This is a fast, non-authoritative check for clear feedback in the
     * common case — it runs before any lock is held, so it cannot alone
     * prevent a race against a concurrent payment being recorded. The
     * authoritative re-check lives in UpdatePurchaseStatus, inside a
     * transaction with the purchase row locked.
     *
     * @return array<string, ValidationRule|array<mixed>|string|Closure>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(Purchase::statuses()),
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var Purchase $purchase */
                    $purchase = $this->route('purchase');

                    if (! $purchase->canTransitionTo($value)) {
                        $fail("Cannot move a purchase from \"{$purchase->status}\" to \"{$value}\".");

                        return;
                    }

                    if ($value === Purchase::STATUS_CANCELLED && $purchase->payments()->exists()) {
                        $fail('Cannot cancel a purchase that already has recorded payments.');
                    }
                },
            ],
        ];
    }
}
