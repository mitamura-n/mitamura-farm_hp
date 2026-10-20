<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\InquiryController;

Route::get('/sitemap.xml', function () {
    abort_unless(config('seo.indexable'), 404);
    return response()->view('sitemap')->header('Content-Type','application/xml; charset=UTF-8');
});

Route::get('/', function () {
    session(['inquiry_token' => (string) Str::uuid()]);
    return response()->view('farm', ['products'=>\App\Models\Product::orderBy('id')->get()->keyBy('name')])->header('Cache-Control', 'no-store, private');
});

Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiries.store');

Route::get('/admin/login', [\App\Http\Controllers\ProductAdminController::class, 'login'])->name('login');
Route::post('/admin/login', [\App\Http\Controllers\ProductAdminController::class, 'authenticate'])->middleware('throttle:5,1');
Route::get('/preview-setup/{token}', [\App\Http\Controllers\PreviewSetupController::class, 'show'])->middleware('throttle:10,1');
Route::post('/preview-setup/{token}', [\App\Http\Controllers\PreviewSetupController::class, 'store'])->middleware('throttle:5,1');
Route::middleware('auth')->group(function () {
    Route::get('/admin/legacy-site/{path?}', [\App\Http\Controllers\LegacySiteController::class, 'show'])->where('path', '.*')->name('admin.legacy-site');
    Route::get('/admin/database-credentials', [\App\Http\Controllers\ProductionDatabaseCredentialController::class, 'show'])->name('admin.database-credentials');
    Route::post('/admin/database-credentials', [\App\Http\Controllers\ProductionDatabaseCredentialController::class, 'store'])->middleware('throttle:5,1')->name('admin.database-credentials.store');
    Route::get('/admin/mail-credentials', [\App\Http\Controllers\MailCredentialController::class, 'show'])->name('admin.mail-credentials');
    Route::post('/admin/mail-credentials', [\App\Http\Controllers\MailCredentialController::class, 'store'])->middleware('throttle:5,1')->name('admin.mail-credentials.store');
    Route::get('/admin/inquiries', [\App\Http\Controllers\InquiryAdminController::class, 'index'])->name('admin.inquiries.index');
    Route::get('/admin/inquiries/{inquiry}', [\App\Http\Controllers\InquiryAdminController::class, 'show'])->name('admin.inquiries.show');
    Route::put('/admin/inquiries/{inquiry}', [\App\Http\Controllers\InquiryAdminController::class, 'update'])->middleware('throttle:30,1')->name('admin.inquiries.update');
    Route::post('/admin/logout', [\App\Http\Controllers\ProductAdminController::class, 'logout']);
    Route::get('/admin/products', [\App\Http\Controllers\ProductAdminController::class, 'index']);
    Route::put('/admin/products/{product}', [\App\Http\Controllers\ProductAdminController::class, 'update']);
});
