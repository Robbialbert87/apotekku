<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\PaymentRecordStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'supplier_id',
    'invoice_number',
    'invoice_date',
    'received_date',
    'due_date',
    'total_amount',
    'notes',
    'created_by',
    'updated_by',
])]
class Invoice extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'received_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(InvoiceFile::class)->latest();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function postedAllocations(): HasMany
    {
        return $this->allocations()
            ->whereHas('payment', fn (Builder $query): Builder => $query->where('status', PaymentRecordStatus::Posted->value));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Subquery SQL untuk total pembayaran yang masih berlaku (payment posted).
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public static function paidAmountSql(): array
    {
        $subquery = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->selectRaw('COALESCE(SUM(payment_allocations.amount), 0)')
            ->whereColumn('payment_allocations.invoice_id', 'invoices.id')
            ->where('payments.status', PaymentRecordStatus::Posted->value);

        return [$subquery->toSql(), $subquery->getBindings()];
    }

    public function scopeNotVoided(Builder $query): void
    {
        $query->whereNull('invoices.voided_at');
    }

    public function scopeVoided(Builder $query): void
    {
        $query->whereNotNull('invoices.voided_at');
    }

    public function scopeOutstanding(Builder $query): void
    {
        [$sql, $bindings] = self::paidAmountSql();

        $query->notVoided()->whereRaw("invoices.total_amount > ({$sql})", $bindings);
    }

    public function scopeWithPaidAmount(Builder $query): void
    {
        $query->withSum('postedAllocations as paid_amount', 'amount');
    }

    public function scopeWherePaymentStatus(Builder $query, ?string $status): void
    {
        if (! in_array($status, ['unpaid', 'partial', 'paid'], true)) {
            return;
        }

        [$sql, $bindings] = self::paidAmountSql();

        match ($status) {
            'unpaid' => $query->whereRaw("({$sql}) <= 0", $bindings),
            'partial' => $query->whereRaw(
                "({$sql}) > 0 AND ({$sql}) < invoices.total_amount",
                [...$bindings, ...$bindings],
            ),
            'paid' => $query->whereRaw("({$sql}) >= invoices.total_amount", $bindings),
        };
    }

    public function scopeWhereDueStatus(Builder $query, ?string $status): void
    {
        $today = today()->toDateString();
        $soon = today()->addDays((int) config('apotek.due_soon_days', 7))->toDateString();

        match ($status) {
            DueStatus::Terlambat->value => $query->whereDate('invoices.due_date', '<', $today),
            DueStatus::JatuhTempoHariIni->value => $query->whereDate('invoices.due_date', $today),
            DueStatus::SegeraJatuhTempo->value => $query->whereDate('invoices.due_date', '>', $today)
                ->whereDate('invoices.due_date', '<=', $soon),
            DueStatus::BelumJatuhTempo->value => $query->whereDate('invoices.due_date', '>', $soon),
            default => null,
        };
    }

    public function scopeDueBetween(Builder $query, ?string $start, ?string $end): void
    {
        if ($start !== null && $start !== '') {
            $query->whereDate('invoices.due_date', '>=', $start);
        }

        if ($end !== null && $end !== '') {
            $query->whereDate('invoices.due_date', '<=', $end);
        }
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('invoices.invoice_number', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn (Builder $supplier): Builder => $supplier->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeForSupplier(Builder $query, int|string|null $supplierId): void
    {
        if ($supplierId === null || $supplierId === '') {
            return;
        }

        $query->where('invoices.supplier_id', $supplierId);
    }

    protected function paidAmount(): Attribute
    {
        return Attribute::get(function (): string {
            $paid = $this->attributes['paid_amount'] ?? null;

            if ($paid === null) {
                $paid = $this->postedAllocations()->sum('amount');
            }

            return Money::fromCents(Money::toCents((string) $paid));
        });
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(function (): string {
            $remaining = Money::toCents($this->total_amount) - Money::toCents($this->paid_amount);

            return Money::fromCents(max(0, $remaining));
        });
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(function (): InvoicePaymentStatus {
            $paid = Money::toCents($this->paid_amount);
            $total = Money::toCents($this->total_amount);

            if ($paid <= 0) {
                return InvoicePaymentStatus::BelumDibayar;
            }

            return $paid >= $total
                ? InvoicePaymentStatus::Lunas
                : InvoicePaymentStatus::DibayarSebagian;
        });
    }

    protected function dueStatus(): Attribute
    {
        return Attribute::get(function (): ?DueStatus {
            if ($this->isVoided() || $this->due_date === null) {
                return null;
            }

            $due = $this->due_date->toDateString();
            $today = today()->toDateString();

            if ($due < $today) {
                return DueStatus::Terlambat;
            }

            if ($due === $today) {
                return DueStatus::JatuhTempoHariIni;
            }

            if ($due <= today()->addDays((int) config('apotek.due_soon_days', 7))->toDateString()) {
                return DueStatus::SegeraJatuhTempo;
            }

            return DueStatus::BelumJatuhTempo;
        });
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === InvoicePaymentStatus::Lunas;
    }
}
