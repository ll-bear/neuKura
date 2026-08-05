<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BookmarkController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('bookmarks/search', [BookmarkController::class, 'search']);
    Route::apiResource('bookmarks', BookmarkController::class, ['only' => ['store', 'update', 'destroy']]);

    // 拡張機能専用: 'credentials:read' abilityを持つトークンのみ許可
    Route::get('/credentials', [CredentialController::class, 'index'])
        ->middleware('ability:credentials:read');

    // neuKura画面側の管理操作: 通常のログインセッション/フルアクセストークン用
    Route::post('/bookmarks/{bookmark}/credentials', [CredentialController::class, 'store']);
    Route::patch('/credentials/{credential}', [CredentialController::class, 'update']);
    Route::delete('/credentials/{credential}', [CredentialController::class, 'destroy']);
});