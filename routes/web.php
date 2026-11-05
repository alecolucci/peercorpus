<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArticleImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScreeningController;
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

    // CRUD de Projetos
    Route::resource('projects', ProjectController::class);

    // Rotas de Importação (vinculadas a um projeto específico)
    Route::post('/projects/{project}/import/upload', [ArticleImportController::class, 'uploadFile'])->name('projects.import.upload');
    Route::post('/projects/{project}/import/process', [ArticleImportController::class, 'processBatch'])->name('projects.import.process');

    // Rotas de Triagem
    Route::get('/projects/{project}/screening', [ScreeningController::class, 'index'])->name('projects.screening');
    Route::put('/articles/{article}/status', [ScreeningController::class, 'updateStatus'])->name('articles.status.update');
});

require __DIR__.'/auth.php';
