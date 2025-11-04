<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NewVerificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\RoleController;


Route::middleware('auth:sanctum')->group(function () {
    // Retourner les documents créés à l'utilisateur actuellement connecté
    Route::get('documents', [DocumentController::class, 'index']);
    Route::post('documents/add', [DocumentController::class, 'create']);
Route::post('documents/create', [DocumentController::class, 'store']);
Route::post('documents/auto/create', [DocumentController::class, 'storeAutomatic']);
Route::post('documents/extract/create', [DocumentController::class, 'storeFromExtraction']);
    Route::put('documents/edit/document/{id}', [DocumentController::class, 'update']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);
  Route::delete('/documents/{document}', [DocumentController::class, 'delete']);

    // Retourner les collaborateurs créés
    Route::get('users', [UserController::class, 'index']);
    Route::post('users/create', [UserController::class, 'store']);
    Route::put('users/edit/user/{id}', [UserController::class, 'update']);

    // Retourner les types aux utilisateurs connectés
    Route::post('types/create', [TypeController::class, 'store']);
    Route::put('types/edit/type/{id}', [TypeController::class, 'update']);
    Route::delete('types/{id}', [TypeController::class, 'destroy']);

    Route::get('roles', [RoleController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);

  

    Route::patch('/users/{id}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.updateStatus');

  // Route pour l'upload d'un document
Route::get('dashboard/reports', [DocumentController::class, 'showAllHistory']);
Route::get('dashboard/history/{id}', [DocumentController::class, 'showHistory']);
Route::get('dashboard/recent-verifications', [VerificationController::class, 'getVerificationHistory']);
Route::get('dashboard/stats', [DocumentController::class, 'statisticsByDay']);
Route::get('dashboard/total-verifications', [DocumentController::class, 'totalVerifications']);
Route::get('dashboard/total-documents', [DocumentController::class, 'totalDocuments']);
Route::get('filterBy/status', [DocumentController::class, 'getVerificationsByStatus']);
Route::get('verifications/stats', [DocumentController::class, 'getVerificationStats']);
Route::get('/me', [AuthController::class, 'me']);
Route::get('/verifications', [NewVerificationController::class, 'index']);

});

// Routes publiques
Route::post('/uploadDocument', [UploadController::class, 'uploadAndExtractDocuments']);
Route::post('/uploadExcel', [UploadController::class, 'uploadExcelfile']);
// Route pour télécharger un document avec le QR code
Route::get('downloadDocumentWithQr/document{Id}', [DocumentController::class, 'downloadDocumentWithQr']);

Route::post('/login', [AuthController::class, 'login']);
Route::get('types', [TypeController::class, 'index']); 
// Route::post('documents/verify-document', [VerificationController::class, 'verifyDoc']);
Route::post('documents/verify', [VerificationController::class, 'verify']);
Route::post('/upload-document', [DocumentController::class, 'uploadDocument']);

Route::get('activities', [DocumentController::class, 'getActivities']);
Route::post('/documents/filter', [DocumentController::class, 'filter']);
Route::post('documents/verify-document', [NewVerificationController::class, 'verifyDoc']);
Route::get('/test-full', function() {
    return response()->json([
        'status_code' => 200,
        'message' => 'API fonctionne parfaitement !',
        'data' => [
            'time' => now()->toDateTimeString(),
            'environment' => env('APP_ENV'),
        ]
    ], 200);
});
