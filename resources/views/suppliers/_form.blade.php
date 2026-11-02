@php
    $isEdit = $supplier->exists;
@endphp

<p class="text-muted text-small" style="margin:0 0 var(--space-4);">
    Field bertanda <span class="text-danger">*</span> wajib diisi.
</p>

<div class="supplier-form-columns">
    <section class="supplier-form-column">
        <h3 class="supplier-form-column__title">Identitas Supplier</h3>
        <p class="supplier-form-column__desc">Data utama dan kontak supplier.</p>

        <div>
            <label class="form-label-custom" for="name">Nama Supplier <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" autocomplete="organization"
                   class="form-control-custom @error('name') is-invalid @enderror"
                   value="{{ old('name', $supplier->name) }}" required maxlength="150"
                   placeholder="Contoh: PT Kimia Farma" aria-required="true">
            @error('name')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="form-label-custom" for="phone">Nomor Telepon <span class="text-danger">*</span></label>
            <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel"
                   class="form-control-custom @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $supplier->phone) }}" required maxlength="30"
                   placeholder="08xxxxxxxxxx" aria-required="true">
            <div class="form-hint">Gunakan nomor yang dapat dihubungi.</div>
            @error('phone')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="form-label-custom" for="contact_name">Nama Kontak</label>
            <input type="text" id="contact_name" name="contact_name" autocomplete="name"
                   class="form-control-custom @error('contact_name') is-invalid @enderror"
                   value="{{ old('contact_name', $supplier->contact_name) }}" maxlength="150"
                   placeholder="Nama PIC">
            @error('contact_name')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>
    </section>

    <section class="supplier-form-column">
        <h3 class="supplier-form-column__title">Alamat & Catatan</h3>
        <p class="supplier-form-column__desc">Alamat lengkap dan informasi tambahan.</p>

        <div>
            <label class="form-label-custom" for="address">Alamat <span class="text-danger">*</span></label>
            <textarea id="address" name="address" rows="4"
                      class="form-control-custom @error('address') is-invalid @enderror"
                      required maxlength="1000" placeholder="Alamat lengkap supplier"
                      aria-required="true">{{ old('address', $supplier->address) }}</textarea>
            @error('address')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="form-label-custom" for="notes">Catatan</label>
            <textarea id="notes" name="notes" rows="4"
                      class="form-control-custom @error('notes') is-invalid @enderror"
                      maxlength="1000" placeholder="Informasi tambahan seperti termin pembayaran atau jam operasional.">{{ old('notes', $supplier->notes) }}</textarea>
            <div class="form-hint">Opsional. Maksimal 1000 karakter.</div>
            @error('notes')<div class="form-error-text">{{ $message }}</div>@enderror
        </div>

        <label class="form-check-custom">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))>
            <span>Supplier aktif dan dapat dipilih pada invoice baru</span>
        </label>
        <div class="form-hint">Supplier nonaktif tidak akan muncul saat membuat invoice baru.</div>
    </section>
</div>

<div class="form-actions">
    <a href="{{ route('suppliers.index') }}" class="btn-custom btn-outline-custom">Batal</a>
    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Supplier' }}</button>
</div>