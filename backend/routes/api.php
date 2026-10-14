<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\DashboardController;
use App\Models\User;

Route::get('/users', function () {
    return response()->json(User::all());
});

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/{exam}', [ExamController::class, 'show']);
Route::post('/exams', [ExamController::class, 'store']);
Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);

Route::post('/exams/{exam}/submit', [ExamController::class, 'submit']);

Route::get('/dashboard', [DashboardController::class, 'index']);
