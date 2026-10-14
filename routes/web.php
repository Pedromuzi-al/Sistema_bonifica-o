<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/votes/{vote}/invalidate', [CycleController::class, 'invalidateVote'])->name('votes.invalidate');
    Route::get('/cycles', [CycleController::class, 'index'])->name('cycles.index');
    Route::post('/cycles', [CycleController::class, 'store'])->name('cycles.store');
    Route::get('/cycles/{cycle}', [CycleController::class, 'show'])->name('cycles.show');
    Route::post('/cycles/{cycle}/open', [CycleController::class, 'open'])->name('cycles.open');
    Route::post('/cycles/{cycle}/close', [CycleController::class, 'close'])->name('cycles.close');
    Route::post('/awards/{award}/approve', [CycleController::class, 'approve'])->name('awards.approve');
    Route::post('/awards/{award}/publish', [CycleController::class, 'publish'])->name('awards.publish');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/audit', [ReportController::class, 'audit'])->name('audit.index');
    Route::post('/evaluation-criteria', [WorkflowController::class, 'criterion'])->name('criteria.store');
    Route::post('/notifications/{notification}/read', [WorkflowController::class, 'readNotification'])->name('notifications.read');
    Route::post('/ideas/{idea}/evaluation', [WorkflowController::class, 'evaluate'])->name('ideas.evaluate');
    Route::post('/ideas/{idea}/plan', [WorkflowController::class, 'plan'])->name('ideas.plan');
    Route::post('/ideas/{idea}/results', [WorkflowController::class, 'results'])->name('ideas.results');
    Route::post('/ideas/{idea}/results/validate', [WorkflowController::class, 'validateResults'])->name('ideas.validate-results');
    Route::post('/ideas/{idea}/publish', [WorkflowController::class, 'publish'])->name('ideas.publish');
    Route::post('/ideas/{idea}/reply', [WorkflowController::class, 'reply'])->name('ideas.reply');
    Route::post('/ideas/{idea}/conflict', [WorkflowController::class, 'conflict'])->name('ideas.conflict');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/create', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->middleware('throttle:30,1')->name('ideas.vote');
    Route::post('/ideas/{idea}/approve', [IdeaController::class, 'approve'])->name('ideas.approve');

    Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation.index');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::get('/profile/{user?}', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile/{user?}', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/permissions/{user?}', [ProfileController::class, 'updatePermissions'])->name('profile.permissions');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
