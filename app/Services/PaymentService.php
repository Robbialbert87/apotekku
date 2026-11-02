<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Format;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * @param  array{
     *     supplier_id: int|string,
     *     payment_date: string,
     *     total_amount: string|int|float,
     *     method: string,
     *     reference_number?: string|null,
     *     notes?: string|null
     * }  $data
     * @param  array<int|string, string|int|float|null>  $allocations  invoice_id => amount
     */
    public function create(array $data, array $allocations, User $user, ?UploadedFile $proof = null): Payment
    {
        $supplier = Supplier::query()->findOrFail($data['supplier_id']);
        $allocations = $this->normalizeAllocations($allocations);
        $totalCents = Money::toCents($data['total_amount']);

        if ($totalCents <= 0) {
            throw ValidationException::withMessages([
                'total_amount' => 'Nominal pembayaran harus lebih besar dari nol.',
            ]);
        }

        if ($allocations === []) {
            throw ValidationException::withMessages([
                'allocations' => 'Pilih minimal satu invoice untuk dialokasikan.',
            ]);
        }

        $allocatedCents = array_sum(array_map(fn (array $row): int => $row['cents'], $allocations));

        if ($allocatedCents > $totalCents) {
            throw ValidationException::withMessages([
                'allocations' => 'Total alokasi tidak boleh melebihi nominal pembayaran.',
            ]);
        }

        if ($allocatedCents !== $totalCents) {
            throw ValidationException::withMessages([
                'allocations' => 'Seluruh nominal pembayaran wajib dialokasikan ke invoice. Sisa yang belum dialokasikan: '
                    .Format::rupiah(Money::fromCents($totalCents - $allocatedCents)).'.',
            ]);
        }

        return DB::transaction(function () use ($data, $allocations, $user, $proof, $supplier, $totalCents): Payment {
            $invoices = Invoice::query()
                ->whereIn('id', array_keys($allocations))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($allocations as $invoiceId => $allocation) {
                $invoice = $invoices->get($invoiceId);

                if ($invoice === null) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Invoice yang dipilih tidak ditemukan.',
                    ]);
                }

                if ((int) $invoice->supplier_id !== (int) $supplier->id) {
                    throw ValidationException::withMessages([
                        'allocations' => "Invoice {$invoice->invoice_number} bukan milik supplier yang dipilih.",
                    ]);
                }

                if ($invoice->isVoided()) {
                    throw ValidationException::withMessages([
                        'allocations' => "Invoice {$invoice->invoice_number} sudah dibatalkan dan tidak dapat menerima pembayaran.",
                    ]);
                }

                $remainingCents = Money::toCents($invoice->remaining_amount);

                if ($remainingCents <= 0) {
                    throw ValidationException::withMessages([
                        'allocations' => "Invoice {$invoice->invoice_number} sudah lunas.",
                    ]);
                }

                if ($allocation['cents'] > $remainingCents) {
                    throw ValidationException::withMessages([
                        'allocations' => "Alokasi untuk invoice {$invoice->invoice_number} melebihi sisa tagihan ("
                            .Format::rupiah(Money::fromCents($remainingCents)).').',
                    ]);
                }
            }

            $proofPath = null;
            $proofName = null;

            if ($proof !== null) {
                $proofPath = $proof->store('apotek/payments/'.now()->format('Y/m'), config('apotek.upload.disk', 'local'));
                $proofName = $proof->getClientOriginalName();
            }

            $payment = Payment::create([
                'payment_number' => $this->generateNumber(Carbon::parse($data['payment_date'])),
                'supplier_id' => $supplier->id,
                'payment_date' => $data['payment_date'],
                'total_amount' => Money::fromCents($totalCents),
                'method' => PaymentMethod::from($data['method']),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'proof_path' => $proofPath,
                'proof_original_name' => $proofName,
                'status' => PaymentRecordStatus::Posted,
                'created_by' => $user->id,
            ]);

            foreach ($allocations as $invoiceId => $allocation) {
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoiceId,
                    'amount' => Money::fromCents($allocation['cents']),
                ]);
            }

            $this->activityLogger->record(
                'payment.created',
                $payment,
                "Pembayaran {$payment->payment_number} sebesar ".Format::rupiah($payment->total_amount).' dicatat.',
                [
                    'supplier_id' => $supplier->id,
                    'allocations' => collect($allocations)->map(fn (array $row): string => $row['decimal'])->all(),
                ],
            );

            return $payment->load('allocations.invoice', 'supplier');
        });
    }

    public function cancel(Payment $payment, User $user, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->isCancelled()) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran ini sudah dibatalkan sebelumnya.',
                ]);
            }

            $lockedPayment->forceFill([
                'status' => PaymentRecordStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancel_reason' => $reason,
            ])->save();

            $this->activityLogger->record(
                'payment.cancelled',
                $lockedPayment,
                "Pembayaran {$lockedPayment->payment_number} dibatalkan.",
                [
                    'reason' => $reason,
                    'total_amount' => $lockedPayment->total_amount,
                    'invoices' => $lockedPayment->allocations()->pluck('invoice_id')->all(),
                ],
            );

            return $lockedPayment->refresh();
        });
    }

    /**
     * @param  array<int|string, string|int|float|null>  $allocations
     * @return array<int, array{cents: int, decimal: string}>
     */
    private function normalizeAllocations(array $allocations): array
    {
        $normalized = [];

        foreach ($allocations as $invoiceId => $amount) {
            $cents = Money::toCents($amount);

            if ($cents <= 0) {
                continue;
            }

            $normalized[(int) $invoiceId] = [
                'cents' => $cents,
                'decimal' => Money::fromCents($cents),
            ];
        }

        return $normalized;
    }

    private function generateNumber(Carbon $date): string
    {
        $prefix = config('apotek.payment_prefix', 'BYR').'-'.$date->format('Ymd').'-';

        $lastNumber = Payment::query()
            ->where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('payment_number')
            ->lockForUpdate()
            ->value('payment_number');

        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, -4)) + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
