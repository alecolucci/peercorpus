<?php

use App\Http\Controllers\ArticleImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\CodingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SynthesisController;
use App\Http\Controllers\ProjectMemberController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/projects/{project}/import', [ArticleImportController::class, 'import'])->name('projects.import');
    Route::get('/projects/{project}/screening', [ScreeningController::class, 'index'])->name('screening.index');
    Route::patch('/projects/{project}/screening/{article}', [ScreeningController::class, 'update'])->name('screening.update');

    // Tela do Workspace de Codificação
    Route::get('/projects/{project}/coding', [CodingController::class, 'index'])->name('coding.index');

    // Gestão de Tags e Aplicação
    Route::post('/projects/{project}/tags', [TagController::class, 'store'])->name('tags.store');
    Route::post('/articles/{article}/tags/attach', [TagController::class, 'attach'])->name('tags.attach');
    Route::post('/articles/{article}/tags/detach', [TagController::class, 'detach'])->name('tags.detach');

    Route::get('/projects/{project}/synthesis', [SynthesisController::class, 'index'])->name('synthesis.index');

    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('project.members.store');
});

require __DIR__.'/auth.php';
