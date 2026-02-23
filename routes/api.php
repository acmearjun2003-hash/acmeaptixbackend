<?php

use App\Http\Controllers\ExamDetailController;
use App\Http\Controllers\ExamMasterController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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



/*
|--------------------------------------------------------------------------
| API Routes — acmeaptix
|--------------------------------------------------------------------------
*/

// ── Roles ────────────────────────────────────────────────────────────────
Route::apiResource('roles', RoleController::class);

// ── Posts (Job Openings) ─────────────────────────────────────────────────
Route::apiResource('posts', PostController::class);

// ── Question Bank ────────────────────────────────────────────────────────
Route::apiResource('questions', QuestionBankController::class);
Route::get('questions/random', [QuestionBankController::class, 'random']);

// ── Exams ────────────────────────────────────────────────────────────────
Route::get ('exams',                  [ExamMasterController::class, 'index']);
Route::get ('exams/{id}',             [ExamMasterController::class, 'show']);
Route::post('exams/start',            [ExamMasterController::class, 'start']);
Route::post('exams/{id}/submit',      [ExamMasterController::class, 'submit']);
Route::get ('exams/{id}/result',      [ExamMasterController::class, 'result']);

// ── Exam Details ─────────────────────────────────────────────────────────
Route::get('exam-details',            [ExamDetailController::class, 'index']);   // ?exam_id=X
Route::get('exam-details/{id}',       [ExamDetailController::class, 'show']);
Route::put('exam-details/{id}',       [ExamDetailController::class, 'update']);
Route::get('candidates/{candidateId}/exam-details', [ExamDetailController::class, 'byCandidate']);



// Protected routes

Route::get   ('users',        [\App\Http\Controllers\UserController::class, 'index'] );
Route::post  ('users',        [\App\Http\Controllers\UserController::class, 'store'] )->name('users.store');
Route::get   ('users/create', [\App\Http\Controllers\UserController::class, 'create']);
Route::get   ('users/{id}',   [\App\Http\Controllers\UserController::class, 'show']  );
Route::get   ('users/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit']);
Route::put   ('users/{id}',   [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
Route::delete('users/{id}',   [\App\Http\Controllers\UserController::class, 'destroy']);


Route::post('/student',[StudentController::class,'index']);


