<?php

use App\Http\Controllers\ExamDetailController;
use App\Http\Controllers\ExamMasterController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


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
Route::get('/roles/dropdown', [RoleController::class, 'fetchUniqueRoles'])
     ->name('selectDropdownRoles');
Route::apiResource('roles', RoleController::class);


// ── Posts (Job Openings) ─────────────────────────────────────────────────

Route::get('/posts/dropdown',[PostController::class,'fetchUniquePosts'])->name('selectDropdownPosts');
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


Route::apiResource('users', UserController::class);

// Route::patch('users/{user}/exam-results', [UserController::class, 'updateExamResults']);

Route::post('/student',[StudentController::class,'index']);


