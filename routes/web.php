<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('questions', QuestionController::class)->except(['show']);
        Route::resource('exams', ExamController::class)->except(['show']);
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::get('/results/{attempt}', [ResultController::class, 'show'])->name('results.show');
    });

Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/attempts', [StudentExamController::class, 'start'])->name('exams.start');
        Route::get('/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('attempts.show');
        Route::patch('/attempts/{attempt}/answers', [StudentExamController::class, 'saveAnswer'])->name('attempts.answers.save');
        Route::post('/attempts/{attempt}', [StudentExamController::class, 'submit'])->name('attempts.submit');
        Route::get('/attempts/{attempt}/result', [StudentExamController::class, 'result'])->name('attempts.result');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
