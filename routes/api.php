<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BookmarkController;
use App\Http\Controllers\API\ExtensionAuthController;
use App\Http\Controllers\API\SecretPickerApiController;
use App\Http\Controllers\CredentialController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ログイン(トークン発行)は未認証でアクセスできる必要があるため auth:sanctum の外に置く
Route::post('/auth/extension/login', [ExtensionAuthController::class, 'login'])
    ->middleware('throttle:6,1'); // ブルートフォース対策で1分に6回まで

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/extension/logout', [ExtensionAuthController::class, 'logout']);

    Route::get('bookmarks/search', [BookmarkController::class, 'search']);
    Route::apiResource('bookmarks', BookmarkController::class, ['only' => ['store', 'update', 'destroy']]);

    // 拡張機能専用: 'credentials:read' abilityを持つトークンのみ許可
    Route::get('/credentials', [CredentialController::class, 'index'])
        ->middleware('ability:credentials:read');

    // neuKura画面側の管理操作: 通常のログインセッション/フルアクセストークン用
    Route::post('/bookmarks/{bookmark}/credentials', [CredentialController::class, 'store']);
    Route::patch('/credentials/{credential}', [CredentialController::class, 'update']);
    Route::delete('/credentials/{credential}', [CredentialController::class, 'destroy']);

    // secrets picker (Chrome拡張機能向けJSON API)
    Route::prefix('secrets')->group(function () {
        Route::get('/picker', [SecretPickerApiController::class, 'index'])
            ->middleware('ability:credentials:read');

        Route::post('/picker/reveal', [SecretPickerApiController::class, 'reveal'])
            ->middleware('ability:credentials:read');

        Route::post('/picker/store', [SecretPickerApiController::class, 'store'])
            ->middleware('ability:credentials:write');

        Route::post('/picker/store-secret', [SecretPickerApiController::class, 'storeSecret'])
            ->middleware('ability:credentials:write');
    });
});