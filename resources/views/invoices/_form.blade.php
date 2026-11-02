@use('App\Support\Format')
@use('App\Support\Money')

@php
    $isEdit = $invoice->exists;
    $paidCents = $isEdit ? Money::toCents($invoice->paid_amount) : 0;
    $locked = $paidCents > 0;
    $itemRows = old('items', $items->map(fn ($item) => [
        'name' => $item->name,
        'quantity' => rtrim(rtrim((string) $item->quantity, '0'), '.'),
        'unit' => $item->unit,
    ])->all());
    $itemRows = empty($itemRows) ? [['name' => '', 'quantity' => '', 'unit' => '']] : array_values($itemRows);
@endphp

<p class="text-muted text-small" style="margin:0 0 var(--space-4);">
    Field bertanda <span class="text-danger">*</span> wajib diisi.
</p>

<div class="content-row">
    <div class="card-custom">
        <div class="card-custom__header"><h3>Informasi Invoice</h3></div>
        <div class="card-custom__body">
            <div class="form-grid">
                <div class="col-12">
                    <label class="form-label-custom" for="supplier_id">Supplier <span class="text-danger">*</span></label>
                    <select id="supplier_id" name="supplier_id" class="form-select-custom form-control-custom @error('supplier_id') is-invalid @enderror" required @disabled($locked)>
                        <option value="">Pilih supplier</option>
                        @foreach ($suppliers as $option)
                            <option value="{{ $option->id }}" @selected((string) old('supplier_id', $invoice->supplier_id) === (string) $option->id)>
                                {{ $option->name }}{{ $option->is_active ? '' : ' (nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($locked)
                        <input type="hidden" name="supplier_id" value="{{ $invoice->supplier_id }}">
                        <div class="form-hint">Supplier tidak dapat diubah karena invoice sudah memiliki pembayaran.</div>
                    @endif
                    @error('supplier_id')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="invoice_number">Nomor Invoice <span class="text-danger">*</span></label>
                    <input type="text" id="invoice_number" name="invoice_number" class="form-control-custom text-uppercase @error('invoice_number') is-invalid @enderror"
                           value="{{ old('invoice_number', $invoice->invoice_number) }}" required maxlength="100" placeholder="INV-0001">
                    <div class="form-hint">Unik untuk setiap supplier.</div>
                    @error('invoice_number')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="total_amount">Total Tagihan <span class="text-danger">*</span></label>
                    <div class="currency-input">
                        <span class="currency-input__prefix">Rp</span>
                        <input type="text" id="total_amount" name="total_amount" inputmode="numeric"
                               class="form-control-custom @error('total_amount') is-invalid @enderror"
                               value="{{ old('total_amount', $isEdit ? Format::rupiah($invoice->total_amount, false) : '') }}"
                               data-currency-input required @readonly($locked)>
                    </div>
                    @if ($locked)
                        <div class="form-hint">Total terkunci karena sudah ada pembayaran. Batalkan pembayaran untuk mengoreksi.</div>
                    @endif
                    @error('total_amount')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="notes">Catatan</label>
                    <textarea id="notes" name="notes" rows="3" class="form-control-custom @error('notes') is-invalid @enderror" maxlength="2000"
                              placeholder="Catatan internal mengenai invoice ini.">{{ old('notes', $invoice->notes) }}</textarea>
                    @error('notes')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>Tanggal</h3></div>
        <div class="card-custom__body">
            <div class="form-grid">
                <div class="col-12">
                    <label class="form-label-custom" for="invoice_date">Tanggal Invoice <span class="text-danger">*</span></label>
                    <input type="text" id="invoice_date" name="invoice_date" class="form-control-custom date-picker @error('invoice_date') is-invalid @enderror"
                           value="{{ old('invoice_date', $invoice->invoice_date?->toDateString()) }}" placeholder="dd/mm/yyyy" required>
                    @error('invoice_date')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="received_date">Tanggal Diterima</label>
                    <input type="text" id="received_date" name="received_date" class="form-control-custom date-picker @error('received_date') is-invalid @enderror"
                           value="{{ old('received_date', $invoice->received_date?->toDateString()) }}" placeholder="dd/mm/yyyy">
                    @error('received_date')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label-custom" for="due_date">Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="text" id="due_date" name="due_date" class="form-control-custom date-picker @error('due_date') is-invalid @enderror"
                           value="{{ old('due_date', $invoice->due_date?->toDateString()) }}" placeholder="dd/mm/yyyy" required>
                    @error('due_date')<div class="form-error-text">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-custom">
    <div class="card-custom__header">
        <h3>Rincian Obat</h3>
        <button type="button" class="btn-custom btn-outline-custom btn-sm-custom" data-add-item>
            <i class="bi bi-plus-lg"></i> Tambah
        </button>
    </div>
    <div class="card-custom__body">
        <p class="text-muted text-small" style="margin:-8px 0 var(--space-4);">Opsional. Cukup nama dan jumlah obat, tanpa harga.</p>

        <div class="item-rows" data-item-rows>
            @foreach ($itemRows as $index => $item)
                <div class="item-row" data-item-row>
                    <input type="text" name="items[{{ $index }}][name]" class="form-control-custom item-name" value="{{ $item['name'] ?? '' }}" placeholder="Nama obat" maxlength="150">
                    <input type="text" name="items[{{ $index }}][quantity]" class="form-control-custom" value="{{ $item['quantity'] ?? '' }}" placeholder="Jumlah" inputmode="decimal">
                    <input type="text" name="items[{{ $index }}][unit]" class="form-control-custom" value="{{ $item['unit'] ?? '' }}" placeholder="Satuan" maxlength="30">
                    <button type="button" class="btn-custom btn-ghost-custom" data-remove-item title="Hapus baris"><i class="bi bi-trash"></i></button>
                </div>
            @endforeach
        </div>

        @error('items.*.name')<div class="form-error-text">{{ $message }}</div>@enderror
        @error('items.*.quantity')<div class="form-error-text">{{ $message }}</div>@enderror

        <div style="margin-top:var(--space-5); padding-top:var(--space-5); border-top:1px solid var(--color-border);">
            <h3 class="form-section__title">Lampiran</h3>
            <p class="text-muted text-small" style="margin:-8px 0 var(--space-4);">Foto atau PDF invoice, maksimal {{ config('apotek.upload.max_kb', 5120) / 1024 }} MB per file (maksimal 5 file).</p>

            @if ($files->isNotEmpty())
                <ul class="file-list" style="margin-bottom:var(--space-4);">
                    @foreach ($files as $file)
                        <li>
                            <div class="file-icon"><i class="bi {{ $file->isImage() ? 'bi-image' : 'bi-file-earmark-pdf' }}"></i></div>
                            <div class="file-meta">
                                <div class="file-name">{{ $file->original_name }}</div>
                                <div class="file-size">{{ Format::fileSize($file->size) }}</div>
                            </div>
                            <a href="{{ route('invoices.files.show', [$invoice, $file]) }}" class="btn-custom btn-ghost-custom btn-sm-custom" target="_blank"><i class="bi bi-eye"></i></a>
                            <form action="{{ route('invoices.files.destroy', [$invoice, $file]) }}" method="POST" data-confirm="Hapus lampiran ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <input type="file" name="files[]" class="form-control-custom" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
            @error('files.*')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a href="{{ $isEdit ? route('invoices.show', $invoice) : route('invoices.index') }}" class="btn-custom btn-outline-custom">Batal</a>
    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Invoice' }}</button>
</div>