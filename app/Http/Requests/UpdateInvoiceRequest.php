<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('invoice.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_number' => mb_strtoupper(trim((string) $this->input('invoice_number'))),
            'total_amount' => Money::toDecimal($this->input('total_amount')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $supplierId = $this->input('supplier_id');

        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number' => [
                'required', 'string', 'max:100',
                Rule::unique('invoices', 'invoice_number')
                    ->where(fn ($query) => $query->where('supplier_id', $supplierId))
                    ->ignore($invoice?->id),
            ],
            'invoice_date' => ['required', 'date'],
            'received_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'total_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:150'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('apotek.upload.max_kb', 5120)],
        ];
    }
}
