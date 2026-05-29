<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Api\BookmarkController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'store']);
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::resource('category', CategoryController::class, ['only' => ['index', 'store', 'update', 'destroy', 'sort']]);
    Route::post('category/sort', [CategoryController::class, 'sort'])->name('category.sort');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('bookmarks/search', [BookmarkController::class, 'search']);
    Route::apiResource('bookmarks', BookmarkController::class, ['only' => ['store', 'destroy']]);
});
