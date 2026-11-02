<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceFileController extends Controller
{
    public function show(Request $request, Invoice $invoice, InvoiceFile $invoiceFile): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('invoice.view'), 403);
        abort_unless((int) $invoiceFile->invoice_id === (int) $invoice->id, 404);

        $disk = Storage::disk(config('apotek.upload.disk', 'local'));

        abort_unless($disk->exists($invoiceFile->path), 404, 'Lampiran tidak ditemukan.');

        if ($request->boolean('download')) {
            return $disk->download($invoiceFile->path, $invoiceFile->original_name);
        }

        return $disk->response($invoiceFile->path, $invoiceFile->original_name, [
            'Content-Type' => $invoiceFile->mime_type,
        ]);
    }
}
