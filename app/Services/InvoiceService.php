<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\User;
use App\Support\Format;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function create(array $data, User $user, array $files = []): Invoice
    {
        return DB::transaction(function () use ($data, $user, $files): Invoice {
            $invoice = Invoice::create([
                ...$this->payload($data),
                'invoice_number' => $this->normalizeNumber($data['invoice_number']),
                'total_amount' => Money::toDecimal($data['total_amount']),
                'created_by' => $user->id,
            ]);

            $this->syncItems($invoice, $data['items'] ?? []);
            $this->storeFiles($invoice, $user, $files);

            $this->activityLogger->record(
                'invoice.created',
                $invoice,
                "Invoice {$invoice->invoice_number} dari {$invoice->supplier->name} dibuat.",
                ['total_amount' => $invoice->total_amount],
            );

            return $invoice->load(['supplier', 'items', 'files']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function update(Invoice $invoice, array $data, User $user, array $files = []): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $user, $files): Invoice {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $paidCents = Money::toCents($locked->paid_amount);
            $newTotalCents = Money::toCents($data['total_amount']);
            $newSupplierId = (int) $data['supplier_id'];

            if ($paidCents > 0 && $newTotalCents !== Money::toCents($locked->total_amount)) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Nominal invoice tidak dapat diubah karena sudah ada pembayaran. Batalkan pembayaran terlebih dahulu bila perlu koreksi.',
                ]);
            }

            if ($paidCents > 0 && $newSupplierId !== (int) $locked->supplier_id) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier tidak dapat diubah karena invoice sudah memiliki pembayaran.',
                ]);
            }

            if ($newTotalCents < $paidCents) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Nominal invoice tidak boleh lebih kecil dari total pembayaran yang sudah tercatat ('
                        .Format::rupiah(Money::fromCents($paidCents)).').',
                ]);
            }

            $locked->fill([
                ...$this->payload($data),
                'invoice_number' => $this->normalizeNumber($data['invoice_number']),
                'total_amount' => Money::fromCents($newTotalCents),
                'updated_by' => $user->id,
            ])->save();

            $this->syncItems($locked, $data['items'] ?? []);
            $this->storeFiles($locked, $user, $files);

            $this->activityLogger->record(
                'invoice.updated',
                $locked,
                "Invoice {$locked->invoice_number} diperbarui.",
                ['changes' => array_keys($locked->getChanges())],
            );

            return $locked->load(['supplier', 'items', 'files']);
        });
    }

    public function void(Invoice $invoice, User $user, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $user, $reason): Invoice {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->isVoided()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice ini sudah dibatalkan.',
                ]);
            }

            if (Money::toCents($locked->paid_amount) > 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice yang sudah memiliki pembayaran tidak dapat dibatalkan. Batalkan pembayaran terlebih dahulu.',
                ]);
            }

            $locked->forceFill([
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ])->save();

            $this->activityLogger->record(
                'invoice.voided',
                $locked,
                "Invoice {$locked->invoice_number} dibatalkan.",
                ['reason' => $reason],
            );

            return $locked->refresh();
        });
    }

    public function deleteFile(InvoiceFile $file, User $user): void
    {
        DB::transaction(function () use ($file): void {
            $invoice = $file->invoice;

            Storage::disk(config('apotek.upload.disk', 'local'))->delete($file->path);
            $file->delete();

            $this->activityLogger->record(
                'invoice.file_deleted',
                $invoice,
                "Lampiran {$file->original_name} pada invoice {$invoice->invoice_number} dihapus.",
            );
        });
    }

    /**
     * @param  array<int, array{name?: string|null, quantity?: mixed, unit?: string|null}>  $items
     */
    public function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        $sort = 0;

        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $invoice->items()->create([
                'name' => $name,
                'quantity' => Money::toDecimal($item['quantity'] ?? 0),
                'unit' => filled($item['unit'] ?? null) ? trim((string) $item['unit']) : null,
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeFiles(Invoice $invoice, User $user, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('apotek/invoices/'.$invoice->id, config('apotek.upload.disk', 'local'));

            $invoice->files()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'supplier_id' => $data['supplier_id'],
            'invoice_date' => $data['invoice_date'],
            'received_date' => $data['received_date'] ?? null,
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function normalizeNumber(string $number): string
    {
        return mb_strtoupper(trim($number));
    }
}
