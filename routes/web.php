<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/create', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->name('ideas.vote');
    Route::post('/ideas/{idea}/approve', [IdeaController::class, 'approve'])->name('ideas.approve');

    Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation.index');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::get('/profile/{user?}', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile/{user?}', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/permissions/{user?}', [ProfileController::class, 'updatePermissions'])->name('profile.permissions');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
