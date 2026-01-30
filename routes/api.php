<?php

// routes/api.php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\VideoController;

use App\Http\Controllers\Api\Admin\AdminCourseController;
use App\Http\Controllers\Api\Admin\AdminModuleController;
use App\Http\Controllers\Api\Admin\AdminLessonController;
use App\Http\Controllers\Api\Admin\AdminReportController;

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\Admin\AdminQuizController;

// routes/api.php
Route::get('/v1/__ping', fn () => response()->json(['ok' => true]));



Route::get('/lessons/{lessonId}/quiz', [AdminQuizController::class, 'show']);
Route::put('/lessons/{lessonId}/quiz', [AdminQuizController::class, 'upsert']);

Route::post('/quizzes/{quizId}/questions', [AdminQuizController::class, 'addQuestion']);
Route::put('/questions/{id}', [AdminQuizController::class, 'updateQuestion']);
Route::delete('/questions/{id}', [AdminQuizController::class, 'deleteQuestion']);


Route::get('/lessons/{lessonId}/quiz', [QuizController::class, 'show']);
Route::post('/lessons/{lessonId}/quiz/submit', [QuizController::class, 'submit']);

Route::prefix('v1')->group(function () {
    Route::post('auth/admin/register', [AdminAuthController::class, 'register']);
});


Route::prefix('v1')->group(function () {

  // Auth
  Route::post('/auth/register', [AuthController::class, 'register']);
  Route::post('/auth/login', [AuthController::class, 'login']);
  Route::post('/auth/otp/resend', [OtpController::class, 'resend']);
  Route::post('/auth/otp/verify-email', [OtpController::class, 'verifyEmail']);
  Route::post('/auth/forgot/send-otp', [OtpController::class, 'forgotSendOtp']);
  Route::post('/auth/forgot/login-with-otp', [OtpController::class, 'loginWithOtp']);

  Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Student APIs
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{courseId}', [CourseController::class, 'show']);

    Route::get('/lessons/{lessonId}', [LessonController::class, 'show']);
    Route::post('/lessons/{lessonId}/read', [LessonController::class, 'markRead']);

    Route::post('/lessons/{lessonId}/video/start', [VideoController::class, 'start']);
    Route::post('/lessons/{lessonId}/video/heartbeat', [VideoController::class, 'heartbeat']);
    Route::post('/lessons/{lessonId}/video/complete', [VideoController::class, 'complete']);

    // Admin APIs
    Route::middleware('admin')->prefix('admin')->group(function () {
      Route::get('/courses', [AdminCourseController::class, 'index']);
      Route::post('/courses', [AdminCourseController::class, 'store']);
      Route::get('/courses/{id}', [AdminCourseController::class, 'show']);
      Route::put('/courses/{id}', [AdminCourseController::class, 'update']);
      Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy']);

      Route::get('/courses/{courseId}/modules', [AdminModuleController::class, 'listByCourse']);
      Route::post('/courses/{courseId}/modules', [AdminModuleController::class, 'store']);
      Route::put('/modules/{id}', [AdminModuleController::class, 'update']);
      Route::delete('/modules/{id}', [AdminModuleController::class, 'destroy']);
      Route::post('/modules/reorder', [AdminModuleController::class, 'reorder']);

      Route::get('/modules/{moduleId}/lessons', [AdminLessonController::class, 'listByModule']);
      Route::post('/modules/{moduleId}/lessons', [AdminLessonController::class, 'store']);
      Route::put('/lessons/{id}', [AdminLessonController::class, 'update']);
      Route::delete('/lessons/{id}', [AdminLessonController::class, 'destroy']);
      Route::post('/lessons/reorder', [AdminLessonController::class, 'reorder']);
      Route::put('/lessons/{lessonId}/content', [AdminLessonController::class, 'upsertContent']);

      // Reports
      Route::get('/reports/overview', [AdminReportController::class, 'overview']);
      Route::get('/reports/progress-funnel', [AdminReportController::class, 'progressFunnel']);
      Route::get('/reports/completion-by-course', [AdminReportController::class, 'completionByCourse']);
      Route::get('/reports/students', [AdminReportController::class, 'students']);
      Route::get('/reports/students/{userId}/progress', [AdminReportController::class, 'studentProgress']);
    });
  });
});
