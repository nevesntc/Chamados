<?php

declare(strict_types=1);
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/workspace');
Route::redirect('/chamados', '/workspace/chamados');

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/cadastro', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::post('/sair', [AuthController::class, 'logout'])->name('logout');
    Route::get('/workspace', [WorkspaceController::class, 'dashboard'])->name('workspace.dashboard');
    Route::get('/workspace/equipe', [WorkspaceController::class, 'team'])->name('workspace.team');
    Route::patch('/workspace/equipe', [WorkspaceController::class, 'rename'])->name('workspace.rename');
    Route::post('/workspace/equipe/sair', [WorkspaceController::class, 'leave'])->name('workspace.leave');
    Route::delete('/workspace/equipe/membros/{member}', [WorkspaceController::class, 'removeMember'])->name('workspace.members.remove');
    Route::post('/workspace/equipe/convites', [WorkspaceController::class, 'invite'])->middleware('throttle:invite')->name('workspace.invite');
    Route::post('/workspace/equipe/entrar', [WorkspaceController::class, 'join'])->middleware('throttle:join')->name('workspace.join');
    Route::post('/workspace/trocar', [WorkspaceController::class, 'switch'])->name('workspace.switch');
    Route::get('/workspace/perfil', [WorkspaceController::class, 'profile'])->name('workspace.profile');
    Route::patch('/workspace/perfil', [WorkspaceController::class, 'updateProfile'])->name('workspace.profile.update');
    Route::put('/workspace/perfil/senha', [WorkspaceController::class, 'updatePassword'])->middleware('throttle:password')->name('workspace.password.update');
    Route::resource('workspace/chamados', TicketController::class)->parameters(['chamados' => 'ticket'])->names('tickets')->except('destroy');
});
