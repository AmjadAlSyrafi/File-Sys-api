<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:sanctum'])->group(function () {

    // Folder Routes
    Route::get('/folders', [FolderController::class, 'index']);
    Route::get('/folders/{id}', [FolderController::class, 'show']);
    Route::get('/folders/files/{id}', [FolderController::class, 'getFiles']);
    Route::post('/folders', [FolderController::class, 'store']);
    Route::delete('/folders/{id}', [FolderController::class, 'destroy']);
    Route::put('/folders/{id}', [FolderController::class, 'update']);
    Route::get('/folders/search/{id}', [FolderController::class, 'search']);
    //Route::put('/folders/{id}/permissions', [FolderController::class, 'updatePermissions']);
    Route::post('/folders/{id}/permissions', [FolderController::class, 'updateUserPermissions']);
    Route::put('/folders/{id}/set-private', [FolderController::class, 'setPrivate']);
    Route::get('/folders/{id}/download', [FolderController::class, 'download']);

    // File Routes
    Route::post('/files', [FileController::class, 'store']);
    Route::get('/files/{id}', [FileController::class, 'show']);
    Route::delete('/files/{id}', [FileController::class, 'destroy']);
    Route::put('/files/{id}/permissions', [FileController::class, 'updatePermissions']);

    //user Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);



    //Serve the Download
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/download/{folderId}/{filePath}', [FolderController::class, 'serveDownload'])
            ->where('filePath', '.*')
            ->name('serveDownload')
            ->middleware('signed');
    });









