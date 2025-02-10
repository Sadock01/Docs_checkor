<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TypeController;

Route::middleware('auth:sanctum')->group(function () {
    // Retourner les documents créés à l'utilisateur actuellement connecté
    Route::get('documents', [DocumentController::class, 'index']);
Route::post('documents/create', [DocumentController::class, 'store']);
    Route::put('documents/edit/document/{id}', [DocumentController::class, 'update']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);


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

  

Route::get('dashboard/recent-verifications', [DocumentController::class, 'getVerificationHistory']);
Route::get('dashboard/stats', [DocumentController::class, 'statisticsByDay']);
Route::get('dashboard/total-verifications', [DocumentController::class, 'totalVerifications']);
Route::get('dashboard/total-documents', [DocumentController::class, 'totalDocuments']);
Route::get('filterBy/status', [DocumentController::class, 'getVerificationsByStatus']);

});

// Routes publiques

Route::post('/login', [AuthController::class, 'login']);
Route::get('types', [TypeController::class, 'index']); 
Route::post('documents/verify-document', [DocumentController::class, 'verifyDocument']);
