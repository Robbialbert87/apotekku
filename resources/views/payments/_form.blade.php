@use('App\Support\Format')

<form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" data-payment-form>
    @csrf

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header"><h3>Informasi Pembayaran</h3></div>
            <div class="card-custom__body">
                <div class="form-grid">
                    <div class="col-12">
                        <label class="form-label-custom" for="supplier_id">Supplier <span class="text-danger">*</span></label>
                        <select id="supplier_id" name="supplier_id" class="form-select-custom form-control-custom @error('supplier_id') is-invalid @enderror" required data-supplier-select>
                            <option value="">Pilih supplier</option>
                            @foreach ($suppliers as $option)
                                <option value="{{ $option->id }}" @selected((string) old('supplier_id', $supplier?->id) === (string) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">Hanya supplier dengan tagihan aktif yang ditampilkan.</div>
                        @error('supplier_id')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom" for="payment_date">Tanggal Pembayaran <span class="text-danger">*</span></label>
                        <input type="text" id="payment_date" name="payment_date" class="form-control-custom date-picker @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', today()->toDateString()) }}" placeholder="dd/mm/yyyy" required>
                        @error('payment_date')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="divider col-12"></div>

                    <div class="col-12">
                        <label class="form-label-custom" for="total_amount">Total Pembayaran <span class="text-danger">*</span></label>
                        <div class="currency-input">
                            <span class="currency-input__prefix">Rp</span>
                            <input type="text" id="total_amount" name="total_amount" inputmode="numeric"
                                   class="form-control-custom @error('total_amount') is-invalid @enderror"
                                   value="{{ old('total_amount') }}" data-currency-input data-payment-total required>
                        </div>
                        @error('total_amount')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom" for="method">Metode <span class="text-danger">*</span></label>
                        <select id="method" name="method" class="form-select-custom form-control-custom @error('method') is-invalid @enderror" required>
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('method')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom" for="reference_number">Nomor Referensi</label>
                        <input type="text" id="reference_number" name="reference_number" class="form-control-custom @error('reference_number') is-invalid @enderror"
                               value="{{ old('reference_number') }}" maxlength="100" placeholder="No. transfer / cek">
                        @error('reference_number')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control-custom @error('notes') is-invalid @enderror" maxlength="2000">{{ old('notes') }}</textarea>
                        @error('notes')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom" for="proof">Bukti Pembayaran</label>
                        <input type="file" id="proof" name="proof" class="form-control-custom" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-hint">Opsional, maksimal {{ config('apotek.upload.max_kb', 5120) / 1024 }} MB.</div>
                        @error('proof')<div class="form-error-text">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom__header"><h3>Ringkasan Alokasi</h3></div>
            <div class="card-custom__body">
                <div class="summary-box">
                    <div class="summary-row"><span>Total Pembayaran</span><span class="money" data-summary-total>Rp 0</span></div>
                    <div class="summary-row"><span>Ter alokasi</span><span class="money" data-summary-allocated>Rp 0</span></div>
                    <div class="summary-row summary-row--total"><span>Belum Dialokasikan</span><span class="money" data-summary-remaining>Rp 0</span></div>
                </div>
                <p class="text-muted text-small" style="margin-top:var(--space-3);" data-allocation-hint>Isi total pembayaran, lalu alokasikan ke invoice.</p>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header">
            <h3>Alokasi Invoice</h3>
            @if ($supplier)
                <span class="text-muted text-small">{{ $invoices->count() }} invoice belum lunas</span>
            @endif
        </div>
        <div class="card-custom__body">
            @error('allocations')<div class="alert-custom alert-danger-custom" style="margin-bottom:var(--space-4);"><i class="bi bi-exclamation-triangle-fill"></i><div>{{ $message }}</div></div>@enderror

            @if (! $supplier)
                <x-empty-state icon="bi-arrow-up-circle" title="Pilih supplier terlebih dahulu" description="Invoice yang belum lunas akan muncul setelah supplier dipilih." />
            @elseif ($invoices->isEmpty())
                <x-empty-state icon="bi-check2-circle" title="Tidak ada tagihan aktif" description="Semua invoice supplier ini sudah lunas atau dibatalkan." />
            @else
                <div class="allocation-list">
                    @foreach ($invoices as $invoice)
                        @php
                            $prefill = old('allocations.'.$invoice->id, (string) $invoiceId === (string) $invoice->id ? Format::rupiah($invoice->remaining_amount, false) : '');
                        @endphp
                        <div class="allocation-row">
                            <div class="allocation-row__meta">
                                <div class="allocation-row__title">
                                    <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                    <x-status-badge :status="$invoice->due_status" />
                                </div>
                                <div class="allocation-row__sub">
                                    {{ $invoice->supplier->name }} · jatuh tempo {{ Format::tanggal($invoice->due_date) }}
                                    · total {{ Format::rupiah($invoice->total_amount) }}
                                    · sisa <strong>{{ Format::rupiah($invoice->remaining_amount) }}</strong>
                                </div>
                            </div>
                            <div class="currency-input">
                                <span class="currency-input__prefix">Rp</span>
                                <input type="text" name="allocations[{{ $invoice->id }}]" inputmode="numeric"
                                       class="form-control-custom" value="{{ $prefill }}"
                                       data-currency-input data-allocation data-max="{{ $invoice->remaining_amount }}"
                                       data-remaining="{{ Format::rupiah($invoice->remaining_amount, false) }}"
                                       placeholder="0">
                            </div>
                            <div style="grid-column:1/-1; display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn-custom btn-ghost-custom btn-sm-custom" data-fill-remaining="{{ $invoice->remaining_amount }}">
                                    <i class="bi bi-check2"></i> Bayar penuh sisa tagihan
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('payments.index') }}" class="btn-custom btn-outline-custom">Batal</a>
        <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> Simpan Pembayaran</button>
    </div>
</form>