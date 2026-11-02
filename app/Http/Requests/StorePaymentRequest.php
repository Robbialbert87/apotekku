<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payment.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $allocations = $this->input('allocations', []);
        $normalized = [];

        if (is_array($allocations)) {
            foreach ($allocations as $invoiceId => $amount) {
                $normalized[$invoiceId] = $amount === null || $amount === ''
                    ? null
                    : Money::toDecimal($amount);
            }
        }

        $this->merge([
            'total_amount' => Money::toDecimal($this->input('total_amount')),
            'allocations' => $normalized,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999999.99'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*' => ['nullable', 'numeric', 'min:0', 'max:999999999999999.99'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('apotek.upload.max_kb', 5120)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'payment_date' => 'tanggal pembayaran',
            'total_amount' => 'total pembayaran',
            'method' => 'metode pembayaran',
            'reference_number' => 'nomor referensi',
            'notes' => 'catatan',
            'allocations' => 'alokasi invoice',
            'proof' => 'bukti pembayaran',
        ];
    }
}
