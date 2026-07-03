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

        // When payable_type doesn't resolve, payable_id gets no `exists`
        // rule at all rather than silently falling back to checking the
        // "purchases" table — otherwise an invalid type paired with an id
        // that happens not to exist in "purchases" reports a confusing,
        // unrelated "payable_id is invalid" error alongside the real
        // "payable_type is invalid" one.
        $payableIdRules = ['required', 'integer'];

        if ($class) {
            $payableIdRules[] = Rule::exists((new $class)->getTable(), 'id');
        }

        return [
            'payable_type' => ['required', 'string', Rule::in(array_keys(Relation::morphMap()))],
            'payable_id' => $payableIdRules,
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) {
                    $payable = $this->resolvePayable();

                    if (! $payable) {
                        return;
                    }

                    $balanceDue = $payable->balanceDue();

                    if ((float) $value > $balanceDue) {
                        $fail("The payment amount exceeds the remaining balance due ({$balanceDue}).");
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
