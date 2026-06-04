<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BookmarkController;

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
    Route::apiResource('bookmarks', BookmarkController::class, ['only' => ['store', 'destroy']]);
});