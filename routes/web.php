<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentGroupController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionImageController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/question-images/{filename}', QuestionImageController::class)
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('question-images.show');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::patch('/student-groups/{student_group}/activate', [StudentGroupController::class, 'activate'])->name('student-groups.activate');
        Route::delete('/student-groups/{student_group}/permanent', [StudentGroupController::class, 'permanentDestroy'])->name('student-groups.permanent-destroy');
        Route::post('/student-groups/{student_group}/assignments', [StudentGroupController::class, 'assignExam'])->name('student-groups.assignments.store');
        Route::resource('student-groups', StudentGroupController::class)->except(['show']);
        Route::patch('/students/{student}/activate', [StudentController::class, 'activate'])->name('students.activate');
        Route::patch('/students/{student}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');
        Route::patch('/students/{student}/remove-group', [StudentController::class, 'removeFromGroup'])->name('students.remove-group');
        Route::patch('/students/{student}/group', [StudentController::class, 'updateGroup'])->name('students.group.update');
        Route::delete('/students/{student}/history', [StudentController::class, 'clearHistory'])->name('students.clear-history');
        Route::delete('/students/{student}/with-history', [StudentController::class, 'destroyWithHistory'])->name('students.destroy-with-history');
        Route::get('/students/import', [StudentController::class, 'import'])->name('students.import');
        Route::post('/students/import', [StudentController::class, 'storeImport'])->name('students.import.store');
        Route::resource('students', StudentController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::post('/students/{student}/assignments', [StudentController::class, 'assignExam'])->name('students.assignments.store');
        Route::delete('/categories/{category}/permanent', [CategoryController::class, 'permanentDestroy'])->name('categories.permanent-destroy');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
        Route::post('/questions/import', [QuestionController::class, 'storeImport'])->name('questions.import.store');
        Route::post('/questions/bulk-actions', [QuestionController::class, 'bulkAction'])->name('questions.bulk-action');
        Route::patch('/questions/{question}/status', [QuestionController::class, 'updateStatus'])->name('questions.status.update');
        Route::get('/questions/{question}/preview', [QuestionController::class, 'preview'])->name('questions.preview');
        Route::delete('/questions/{question}/permanent', [QuestionController::class, 'permanentDestroy'])->name('questions.permanent-destroy');
        Route::resource('questions', QuestionController::class)->except(['show']);
        Route::delete('/exams/{exam}/permanent', [ExamController::class, 'permanentDestroy'])->name('exams.permanent-destroy');
        Route::resource('exams', ExamController::class)->except(['show']);
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::delete('/results/bulk-clear', [ResultController::class, 'bulkDestroy'])->name('results.bulk-destroy');
        Route::get('/results/{attempt}', [ResultController::class, 'show'])->name('results.show');
        Route::post('/results/{attempt}/retake', [ResultController::class, 'grantRetake'])->name('results.retake');
        Route::delete('/results/{attempt}', [ResultController::class, 'destroy'])->name('results.destroy');
    });

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/attempts', [StudentExamController::class, 'start'])->name('exams.start');
        Route::get('/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('attempts.show');
        Route::patch('/attempts/{attempt}/pause', [StudentExamController::class, 'pause'])->name('attempts.pause');
        Route::patch('/attempts/{attempt}/progress', [StudentExamController::class, 'saveProgress'])->name('attempts.progress.save');
        Route::patch('/attempts/{attempt}/answers', [StudentExamController::class, 'saveAnswer'])->name('attempts.answers.save');
        Route::post('/attempts/{attempt}', [StudentExamController::class, 'submit'])->name('attempts.submit');
        Route::get('/attempts/{attempt}/result', [StudentExamController::class, 'result'])->name('attempts.result');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
