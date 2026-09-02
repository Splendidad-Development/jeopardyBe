<?php

use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Sections & Questions Routes
|--------------------------------------------------------------------------
*/
Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
Route::get('/sections/{section}', [SectionController::class, 'show'])->name('sections.show');
Route::get('/sections/{section}/questions', [SectionController::class, 'questions'])->name('sections.questions');
Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');

// Moderator section & question management
Route::middleware('moderator')->group(function () {
    Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::post('/sections/{section}/questions', [QuestionController::class, 'store'])->name('sections.questions.store');
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
});

/*
|--------------------------------------------------------------------------
| Game Session Management Routes
|--------------------------------------------------------------------------
*/
Route::get('/games', [GameSessionController::class, 'index'])->name('games.index');
Route::post('/games', [GameSessionController::class, 'store'])->name('games.store');
Route::get('/games/{game}', [GameSessionController::class, 'show'])->name('games.show');
Route::post('/games/{game}/start', [GameSessionController::class, 'start'])->name('games.start');
Route::get('/games/{game}/current-question', [GameSessionController::class, 'currentQuestion'])->name('games.current-question');
Route::post('/games/{game}/next-question', [GameSessionController::class, 'nextQuestion'])->name('games.next-question');
Route::post('/games/{game}/restart', [GameSessionController::class, 'restart'])->name('games.restart');

/*
|--------------------------------------------------------------------------
| Scoring Routes (Moderator-managed)
|--------------------------------------------------------------------------
*/
Route::get('/games/{game}/scores', [ScoreController::class, 'index'])->name('scores.index');
Route::get('/games/{game}/scores/logs', [ScoreController::class, 'logs'])->name('scores.logs');

Route::middleware('moderator')->group(function () {
    Route::put('/games/{game}/scores', [ScoreController::class, 'update'])->name('scores.update');
    Route::post('/games/{game}/scores/adjust', [ScoreController::class, 'adjust'])->name('scores.adjust');
});

/*
|--------------------------------------------------------------------------
| Leaderboard Routes
|--------------------------------------------------------------------------
*/
Route::get('/games/{game}/leaderboard', [LeaderboardController::class, 'gameLeaderboard'])->name('games.leaderboard');
Route::get('/leaderboard', [LeaderboardController::class, 'globalLeaderboard'])->name('leaderboard.index');
