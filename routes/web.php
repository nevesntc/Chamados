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
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/cadastro', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::post('/sair', [AuthController::class, 'logout'])->name('logout');
    Route::get('/workspace', [WorkspaceController::class, 'dashboard'])->name('workspace.dashboard');
    Route::get('/workspace/equipe', [WorkspaceController::class, 'team'])->name('workspace.team');
    Route::post('/workspace/equipe/convites', [WorkspaceController::class, 'invite'])->middleware('throttle:5,1')->name('workspace.invite');
    Route::post('/workspace/equipe/entrar', [WorkspaceController::class, 'join'])->middleware('throttle:10,1')->name('workspace.join');
    Route::post('/workspace/trocar', [WorkspaceController::class, 'switch'])->name('workspace.switch');
    Route::get('/workspace/perfil', [WorkspaceController::class, 'profile'])->name('workspace.profile');
    Route::patch('/workspace/perfil', [WorkspaceController::class, 'updateProfile'])->name('workspace.profile.update');
    Route::put('/workspace/perfil/senha', [WorkspaceController::class, 'updatePassword'])->middleware('throttle:5,1')->name('workspace.password.update');
    Route::resource('workspace/chamados', TicketController::class)->parameters(['chamados' => 'ticket'])->names('tickets')->except('destroy');
});
