<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

Route::get('/auth-fail', function() {
    return response()->json('user not logged in', 403);
})->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'read']);
    Route::post('/projects', [ProjectController::class, 'make']);
    Route::get('/projects/{id}', [ProjectController::class, 'pick']);
    Route::put('/projects/{id}', [ProjectController::class, 'edit']);
    Route::delete('/projects/{id}', [ProjectController::class, 'remove']);

    Route::get('/tasks', [TaskController::class, 'read']);
    Route::post('/tasks', [TaskController::class, 'make']);
    Route::get('/tasks/{id}', [TaskController::class, 'pick']);
    Route::put('/tasks/{id}', [TaskController::class, 'edit']);
    Route::delete('/tasks/{id}', [TaskController::class, 'remove']);
    Route::put('/tasks/toggle/{id}', [TaskController::class, 'toggle_done']);
});
