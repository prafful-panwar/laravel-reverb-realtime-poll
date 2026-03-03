<?php

use App\Http\Controllers\ProfileController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/polls/{poll}/results', [\App\Http\Controllers\Admin\PollResultController::class, 'show'])->name('admin.polls.show');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('polls', App\Http\Controllers\Admin\PollController::class)->except(['show', 'edit', 'update', 'destroy']);
});

Route::get('/polls/{poll}', [App\Http\Controllers\PollController::class, 'show'])->name('polls.show');
Route::post('/polls/{poll}/options/{optionId}/vote', [App\Http\Controllers\VoteController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('polls.vote');

require __DIR__.'/auth.php';
