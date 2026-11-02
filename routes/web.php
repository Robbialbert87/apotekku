<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceFileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    /*
    | Supplier
    */
    Route::resource('supplier', SupplierController::class)
        ->only(['create', 'store'])
        ->names('suppliers')
        ->parameters(['supplier' => 'supplier'])
        ->middleware('permission:supplier.create');
    Route::resource('supplier', SupplierController::class)
        ->only(['edit', 'update'])
        ->names('suppliers')
        ->parameters(['supplier' => 'supplier'])
        ->middleware('permission:supplier.update');
    Route::resource('supplier', SupplierController::class)
        ->only(['index', 'show'])
        ->names('suppliers')
        ->parameters(['supplier' => 'supplier'])
        ->middleware('permission:supplier.view');
    Route::patch('/supplier/{supplier}/status', [SupplierController::class, 'toggleStatus'])
        ->middleware('permission:supplier.deactivate')
        ->name('suppliers.toggle-status');
    Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:supplier.update')
        ->name('suppliers.destroy');

    /*
    | Invoice
    */
    Route::resource('invoice', InvoiceController::class)
        ->only(['create', 'store'])
        ->names('invoices')
        ->parameters(['invoice' => 'invoice'])
        ->middleware('permission:invoice.create');
    Route::resource('invoice', InvoiceController::class)
        ->only(['edit', 'update'])
        ->names('invoices')
        ->parameters(['invoice' => 'invoice'])
        ->middleware('permission:invoice.update');
    Route::resource('invoice', InvoiceController::class)
        ->only(['index', 'show'])
        ->names('invoices')
        ->parameters(['invoice' => 'invoice'])
        ->middleware('permission:invoice.view');
    Route::delete('/invoice/{invoice}', [InvoiceController::class, 'destroy'])
        ->middleware('permission:invoice.update')
        ->name('invoices.destroy');
    Route::post('/invoice/{invoice}/void', [InvoiceController::class, 'void'])
        ->middleware('permission:invoice.void')
        ->name('invoices.void');
    Route::get('/invoice/{invoice}/files/{invoiceFile}', [InvoiceFileController::class, 'show'])
        ->middleware('permission:invoice.view')
        ->name('invoices.files.show');
    Route::delete('/invoice/{invoice}/files/{invoiceFile}', [InvoiceController::class, 'destroyFile'])
        ->middleware('permission:invoice.update')
        ->name('invoices.files.destroy');

    /*
    | Pembayaran
    */
    Route::resource('pembayaran', PaymentController::class)
        ->only(['create', 'store'])
        ->names('payments')
        ->parameters(['pembayaran' => 'payment'])
        ->middleware('permission:payment.create');
    Route::resource('pembayaran', PaymentController::class)
        ->only(['index', 'show'])
        ->names('payments')
        ->parameters(['pembayaran' => 'payment'])
        ->middleware('permission:payment.view');
    Route::delete('/pembayaran/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('permission:payment.create')
        ->name('payments.destroy');
    Route::post('/pembayaran/{payment}/cancel', [PaymentController::class, 'cancel'])
        ->middleware('permission:payment.cancel')
        ->name('payments.cancel');
    Route::get('/pembayaran/{payment}/proof', [PaymentController::class, 'proof'])
        ->middleware('permission:payment.view')
        ->name('payments.proof');

    Route::get('/rencana-pembayaran', [PaymentPlanController::class, 'index'])
        ->middleware('permission:plan.view')
        ->name('plans.index');

    Route::get('/laporan', [ReportController::class, 'index'])
        ->middleware('permission:report.view')
        ->name('reports.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])
        ->middleware('permission:report.export')
        ->name('reports.export');

    /*
    | Pengguna & peran
    */
    Route::resource('pengguna', UserController::class)
        ->only(['create', 'store'])
        ->names('users')
        ->parameters(['pengguna' => 'user'])
        ->middleware('permission:user.create');
    Route::resource('pengguna', UserController::class)
        ->only(['edit', 'update'])
        ->names('users')
        ->parameters(['pengguna' => 'user'])
        ->middleware('permission:user.update');
    Route::resource('pengguna', UserController::class)
        ->only(['index'])
        ->names('users')
        ->parameters(['pengguna' => 'user'])
        ->middleware('permission:user.view');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:user.update')
        ->name('users.destroy');
    Route::patch('/pengguna/{user}/status', [UserController::class, 'toggleStatus'])
        ->middleware('permission:user.deactivate')
        ->name('users.toggle-status');

    Route::get('/peran', [RoleController::class, 'index'])
        ->middleware('permission:role.view')
        ->name('roles.index');
    Route::get('/peran/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:role.update')
        ->name('roles.edit');
    Route::put('/peran/{role}', [RoleController::class, 'update'])
        ->middleware('permission:role.update')
        ->name('roles.update');

    Route::get('/pengaturan', [SettingController::class, 'edit'])
        ->middleware('permission:setting.view')
        ->name('settings.edit');
    Route::put('/pengaturan', [SettingController::class, 'update'])
        ->middleware('permission:setting.update')
        ->name('settings.update');

    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])
        ->middleware('permission:activity.view')
        ->name('activity.index');
});
