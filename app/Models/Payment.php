<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payment_number',
    'supplier_id',
    'payment_date',
    'total_amount',
    'method',
    'reference_number',
    'notes',
    'proof_path',
    'proof_original_name',
    'status',
    'created_by',
])]
class Payment extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentRecordStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'payment_allocations')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopePosted(Builder $query): void
    {
        $query->where('payments.status', PaymentRecordStatus::Posted->value);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('payments.status', PaymentRecordStatus::Cancelled->value);
    }

    public function scopeForSupplier(Builder $query, int|string|null $supplierId): void
    {
        if ($supplierId === null || $supplierId === '') {
            return;
        }

        $query->where('payments.supplier_id', $supplierId);
    }

    public function scopeBetweenDates(Builder $query, ?string $start, ?string $end): void
    {
        if ($start !== null && $start !== '') {
            $query->whereDate('payments.payment_date', '>=', $start);
        }

        if ($end !== null && $end !== '') {
            $query->whereDate('payments.payment_date', '<=', $end);
        }
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('payments.payment_number', 'like', "%{$term}%")
                ->orWhere('payments.reference_number', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn (Builder $supplier): Builder => $supplier->where('name', 'like', "%{$term}%"));
        });
    }

    protected function allocatedAmount(): Attribute
    {
        return Attribute::get(function (): string {
            $allocated = $this->attributes['allocated_amount'] ?? null;

            if ($allocated === null) {
                $allocated = $this->allocations()->sum('amount');
            }

            return Money::fromCents(Money::toCents((string) $allocated));
        });
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentRecordStatus::Cancelled;
    }
}
