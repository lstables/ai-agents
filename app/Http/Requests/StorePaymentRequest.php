<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $class = $this->resolvePayableClass();
        $table = $class ? (new $class)->getTable() : 'purchases';

        return [
            'payable_type' => ['required', 'string', Rule::in(array_keys(Relation::morphMap()))],
            'payable_id' => ['required', 'integer', Rule::exists($table, 'id')],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) {
                    $payable = $this->resolvePayable();

                    if ($payable && (float) $value > $payable->balanceDue()) {
                        $fail("The payment amount exceeds the remaining balance due ({$payable->balanceDue()}).");
                    }
                },
            ],
            'payment_date' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Resolve the payable class for the current request's payable_type,
     * via the enforced morph map — never from raw client input directly.
     *
     * @return class-string<Model>|null
     */
    public function resolvePayableClass(): ?string
    {
        return Relation::getMorphedModel((string) $this->input('payable_type')) ?: null;
    }

    /**
     * Resolve the payable model for the current request, or null if the
     * type/id combination hasn't passed its own validation rules yet.
     */
    public function resolvePayable(): ?Model
    {
        $class = $this->resolvePayableClass();

        return $class ? $class::find($this->input('payable_id')) : null;
    }
}
