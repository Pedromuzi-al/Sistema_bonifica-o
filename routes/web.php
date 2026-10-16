<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('/evaluation-criteria/{criterion}', [GovernanceController::class, 'criterion'])->name('criteria.update');
    Route::patch('/cycles/{cycle}/draft', [GovernanceController::class, 'draft'])->name('cycles.draft');
    Route::post('/cycles/{cycle}/cancel', [GovernanceController::class, 'cancel'])->name('cycles.cancel');
    Route::post('/cycles/{cycle}/ideas/{idea}/review', [GovernanceController::class, 'review'])->name('cycles.review');
    Route::post('/cycles/{cycle}/ideas/{idea}/finalize', [GovernanceController::class, 'finalize'])->name('cycles.finalize');
    Route::put('/ideas/{idea}/contributors', [GovernanceController::class, 'contributors'])->name('ideas.contributors');
    Route::post('/ideas/{idea}/documents', [GovernanceController::class, 'upload'])->middleware('throttle:10,1')->name('ideas.documents.store');
    Route::get('/ideas/{idea}/documents/{document}', [GovernanceController::class, 'download'])->whereNumber('document')->middleware('throttle:30,1')->name('ideas.documents.download');
    Route::get('/privacy/export', [GovernanceController::class, 'export'])->middleware(['password.confirm', 'throttle:3,1'])->name('privacy.export');
    Route::post('/privacy/requests', [GovernanceController::class, 'privacy'])->middleware(['password.confirm', 'throttle:3,1'])->name('privacy.request');
    Route::get('/admin/privacy', [GovernanceController::class, 'privacyIndex'])->name('admin.privacy');
    Route::post('/admin/privacy/{privacy}', [GovernanceController::class, 'privacyDecision'])->whereNumber('privacy')->name('admin.privacy.decision');

    Route::post('/votes/{vote}/invalidate', [CycleController::class, 'invalidateVote'])->name('votes.invalidate');
    Route::get('/cycles', [CycleController::class, 'index'])->name('cycles.index');
    Route::post('/cycles', [CycleController::class, 'store'])->name('cycles.store');
    Route::get('/cycles/{cycle}', [CycleController::class, 'show'])->name('cycles.show');
    Route::post('/cycles/{cycle}/open', [CycleController::class, 'open'])->name('cycles.open');
    Route::post('/cycles/{cycle}/close', [CycleController::class, 'close'])->name('cycles.close');
    Route::post('/awards/{award}/reject', [GovernanceController::class, 'rejectAward'])->name('awards.reject');
    Route::post('/awards/{award}/approve', [CycleController::class, 'approve'])->name('awards.approve');
    Route::post('/awards/{award}/publish', [CycleController::class, 'publish'])->name('awards.publish');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/audit', [ReportController::class, 'audit'])->name('audit.index');
    Route::post('/evaluation-criteria', [WorkflowController::class, 'criterion'])->name('criteria.store');
    Route::get('/notifications', [WorkflowController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read-all', [WorkflowController::class, 'readAllNotifications'])->name('notifications.read-all');
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
    Route::post('/ideas/review', [IdeaController::class, 'review'])->name('ideas.review');
    Route::get('/ideas/{idea}/voting/{cycle}', [IdeaController::class, 'voteForm'])->name('ideas.vote-form');
    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->middleware('throttle:30,1')->name('ideas.vote');
    Route::post('/ideas/{idea}/approve', [IdeaController::class, 'approve'])->name('ideas.approve');

    Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation.index');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->withTrashed()->name('admin.users.edit');
    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])->withTrashed()->name('admin.users.update');
    Route::post('/admin/users/{user}/block', [AdminUserController::class, 'block'])->name('admin.users.block');
    Route::post('/admin/users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->withTrashed()->name('admin.users.reactivate');
    Route::post('/admin/users/{user}/verification', [AdminUserController::class, 'resendVerification'])->middleware('throttle:6,1')->name('admin.users.verification');
    Route::get('/admin/organization', [OrganizationController::class, 'index'])->name('admin.organization');
    Route::get('/admin/sectors/create', [OrganizationController::class, 'sectorForm'])->name('admin.sectors.create');
    Route::get('/admin/sectors/{sector}/edit', [OrganizationController::class, 'sectorForm'])->name('admin.sectors.edit');
    Route::post('/admin/sectors', [OrganizationController::class, 'saveSector'])->name('admin.sectors.store');
    Route::patch('/admin/sectors/{sector}', [OrganizationController::class, 'saveSector'])->name('admin.sectors.update');
    Route::get('/admin/teams/create', [OrganizationController::class, 'teamForm'])->name('admin.teams.create');
    Route::get('/admin/teams/{team}/edit', [OrganizationController::class, 'teamForm'])->name('admin.teams.edit');
    Route::post('/admin/teams', [OrganizationController::class, 'saveTeam'])->name('admin.teams.store');
    Route::patch('/admin/teams/{team}', [OrganizationController::class, 'saveTeam'])->name('admin.teams.update');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile/{user?}', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile/{user?}', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/permissions/{user?}', [ProfileController::class, 'updatePermissions'])->name('profile.permissions');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
