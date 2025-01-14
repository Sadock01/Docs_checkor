<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TypeController;

Route::middleware('auth:sanctum')->group(function () {
    // Retourner les documents créés à l'utilisateur actuellement connecté
    Route::post('documents/create', [DocumentController::class, 'store']);
    Route::put('documents/edit/document{id}', [DocumentController::class, 'update']);

    // Retourner les collaborateurs créés
    Route::get('users', [UserController::class, 'index']);
    Route::post('users/create', [UserController::class, 'store']);
    Route::put('users/edit/user{id}', [UserController::class, 'update']);

    // Retourner les types aux utilisateurs connectés
    Route::post('types/create', [TypeController::class, 'store']);
    Route::put('types/edit/type{id}', [TypeController::class, 'update']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Retourner l'utilisateur actuellement connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::patch('/users/{id}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.updateStatus');
});

// Routes publiques
Route::get('documents', [DocumentController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('types', [TypeController::class, 'index']);
