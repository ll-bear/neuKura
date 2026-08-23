<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkWebController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SecretController;
use App\Http\Controllers\SecretPickerController;
use App\Http\Controllers\TokenController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'store']);
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/bookmarks', [BookmarkWebController::class, 'index'])->name('bookmarks.index');
    
    Route::resource('category', CategoryController::class, ['only' => ['index', 'store', 'update', 'destroy', 'sort']]);
    Route::post('category/sort', [CategoryController::class, 'sort'])->name('category.sort');

    // トークン管理
    Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy'])->name('tokens.destroy');
});

Route::middleware(['auth'])->prefix('secrets')->name('secrets.')->group(function () {
    Route::get('/picker', [SecretPickerController::class, 'index'])->name('picker');
    Route::post('/picker/reveal', [SecretPickerController::class, 'reveal'])->name('picker.reveal');
    Route::post('/picker/store', [SecretPickerController::class, 'store'])->name('picker.store');
    Route::post('/picker/store-secret', [SecretPickerController::class, 'storeSecret'])->name('picker.store-secret');

    // シークレット管理画面(一覧・編集・削除)
    Route::get('/', [SecretController::class, 'index'])->name('index');
    Route::get('/{secret}/edit', [SecretController::class, 'edit'])->name('edit');
    Route::patch('/{secret}', [SecretController::class, 'update'])->name('update');
    Route::delete('/{secret}', [SecretController::class, 'destroy'])->name('destroy');

    // ログイン情報(credential)の編集・削除もここに統合
    Route::get('/credential/{credential}/edit', [SecretController::class, 'editCredential'])->name('credential.edit');
    Route::patch('/credential/{credential}', [SecretController::class, 'updateCredential'])->name('credential.update');
    Route::delete('/credential/{credential}', [SecretController::class, 'destroyCredential'])->name('credential.destroy');
});