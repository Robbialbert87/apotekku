<?php

use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\Supplier;
use App\Services\InvoiceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->admin = createAdmin();
    Storage::fake('local');
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('invoice attachment is private and requires authentication', function (): void {
    $invoice = Invoice::factory()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $file = InvoiceFile::factory()->create([
        'invoice_id' => $invoice->id,
        'path' => 'apotek/invoices/'.$invoice->id.'/secret.pdf',
        'original_name' => 'secret.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1234,
        'uploaded_by' => $this->admin->id,
    ]);

    Storage::disk('local')->put($file->path, 'rahasia');

    $this->get(route('invoices.files.show', [$invoice, $file]))->assertRedirect(route('login'));

    $this->actingAs($this->admin)
        ->get(route('invoices.files.show', [$invoice, $file]))
        ->assertOk();
});

test('upload via service stores files on private disk', function (): void {
    $invoice = Invoice::factory()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $uploaded = UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf');

    $service = app(InvoiceService::class);
    $updated = $service->update($invoice, [
        'supplier_id' => $this->supplier->id,
        'invoice_number' => $invoice->invoice_number,
        'invoice_date' => $invoice->invoice_date->toDateString(),
        'received_date' => null,
        'due_date' => $invoice->due_date->toDateString(),
        'total_amount' => $invoice->total_amount,
        'notes' => $invoice->notes,
        'items' => [],
    ], $this->admin, [$uploaded]);

    expect($updated->files)->toHaveCount(1);
    Storage::disk('local')->assertExists($updated->files->first()->path);
});
