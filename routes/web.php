<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InsurerController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FactoringController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\InvoiceQueryController;
use App\Http\Controllers\InvoiceReconciliationController;
use App\Http\Controllers\InvoiceReconciliationItemController;
use App\Http\Controllers\AnalystController;
use App\Http\Controllers\KontabBillingSettingsController;
use App\Http\Controllers\KontabWebhookController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

// Webhook entrante de kontab-erp (HMAC verificado en el controller; CSRF excluido en bootstrap/app.php).
Route::post('/webhooks/kontab', [KontabWebhookController::class, 'handle'])
    ->name('webhooks.kontab');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/doctors/{doctor}/user', [DoctorController::class, 'storeUser'])
    ->name('doctors.user.store');

Route::patch('/doctors/{doctor}/user/reset-password', [DoctorController::class, 'resetUserPassword'])
    ->name('doctors.user.reset-password');

    Route::get('/invoices/search', [InvoiceController::class, 'search'])->name('invoices.search');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Gestión de analistas (solo admin)
    Route::get('/analysts', [AnalystController::class, 'index'])->name('analysts.index');
    Route::post('/analysts', [AnalystController::class, 'store'])->name('analysts.store');
    Route::get('/analysts/{analyst}', [AnalystController::class, 'show'])->name('analysts.show');
    Route::delete('/analysts/{analyst}', [AnalystController::class, 'destroy'])->name('analysts.destroy');
    Route::patch('/analysts/{analyst}/reset-password', [AnalystController::class, 'resetPassword'])->name('analysts.reset-password');
    Route::resource('insurers', InsurerController::class)->except(['create', 'edit', 'show']);
    Route::resource('doctors', DoctorController::class)->except(['create', 'edit', 'show']);
    Route::resource('invoices', InvoiceController::class)->only(['index', 'store', 'destroy']);

    // Settings de facturación electrónica (kontab-erp)
    Route::get('/settings/kontab-billing', [KontabBillingSettingsController::class, 'edit'])->name('settings.kontab-billing.edit');
    Route::post('/settings/kontab-billing', [KontabBillingSettingsController::class, 'update'])->name('settings.kontab-billing.update');
    Route::post('/settings/kontab-billing/test', [KontabBillingSettingsController::class, 'test'])->name('settings.kontab-billing.test');
    Route::get('invoices/preview-ncf', [InvoiceController::class, 'previewNcf'])
        ->name('invoices.preview-ncf');
    Route::get('invoices/doctor-data', [InvoiceController::class, 'doctorData'])
        ->name('invoices.doctor-data');

    Route::get('/doctors/{doctor}/insurers', [DoctorController::class, 'insurers'])
        ->name('doctors.insurers');

    Route::get('invoices/{invoice}/view', [InvoiceController::class, 'view'])
        ->name('invoices.view');

    Route::patch('invoices/{invoice}/void', [InvoiceController::class, 'void'])
        ->name('invoices.void');

    Route::get('/invoice-query', [InvoiceQueryController::class, 'index'])->name('invoice-query.index');
    Route::get('/invoice-query/search', [InvoiceQueryController::class, 'search'])->name('invoice-query.search');

    Route::post('invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    Route::delete('invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'destroy'])->name('invoices.payments.destroy');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Endpoint para buscar facturas desde el módulo de pagos (AJAX)
    Route::get('/payments/invoice-search', [PaymentController::class, 'invoiceSearch'])->name('payments.invoice-search');
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('{invoice}/view', [InvoiceController::class, 'view'])->name('view');
        Route::get('{invoice}/pdf',  [InvoiceController::class, 'pdf'])->name('pdf');
    });
    Route::post('/invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoice-items.store');
    Route::patch('/invoice-items/{item}', [InvoiceItemController::class, 'update'])->name('invoice-items.update');
    Route::delete('/invoice-items/{item}', [InvoiceItemController::class, 'destroy'])->name('invoice-items.destroy');

      Route::get('/factorings', [FactoringController::class, 'index'])->name('factorings.index');

    Route::get('/factorings/create', [FactoringController::class, 'create'])->name('factorings.create');
    Route::post('/factorings', [FactoringController::class, 'store'])->name('factorings.store');

    Route::get('/factorings/{factoring}', [FactoringController::class, 'show'])->name('factorings.show');

    Route::patch('/factorings/{factoring}/collect', [FactoringController::class, 'markCollected'])->name('factorings.collect');
    Route::patch('/factorings/{factoring}/cancel', [FactoringController::class, 'cancel'])->name('factorings.cancel');
    
    Route::post('/invoices/{invoice}/reconciliations', [InvoiceReconciliationController::class, 'store'])
    ->name('invoices.reconciliations.store');

Route::patch('/reconciliations/{reconciliation}/close', [InvoiceReconciliationController::class, 'close'])
    ->name('reconciliations.close');

Route::patch('/reconciliation-items/{item}', [InvoiceReconciliationItemController::class, 'update'])
    ->name('reconciliation-items.update');

    // AUTO: subir PDF y prellenar (queda en status=review)
Route::post('/invoices/{invoice}/reconciliations/auto-upload', [InvoiceReconciliationController::class, 'autoUpload'])
  ->name('invoices.reconciliations.auto-upload');

// Confirmar conciliación prellenada -> pasa a open
Route::patch('/reconciliations/{reconciliation}/confirm', [InvoiceReconciliationController::class, 'confirm'])
  ->name('reconciliations.confirm');



    
});




require __DIR__ . '/auth.php';
