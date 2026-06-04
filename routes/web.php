<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkWebController;
use App\Http\Controllers\CategoryController;
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
