<?php

use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\BeliefController;
use App\Http\Controllers\Api\ExperimentController;
use App\Http\Controllers\Api\HabitController;
use App\Http\Controllers\Api\QuestController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\ClerkAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(ClerkAuth::class)->group(function () {
    // User routes
    Route::get('/user', [UserController::class, 'show']);
    Route::patch('/user', [UserController::class, 'update']);

    // Assessment routes
    Route::get('/assessments/current-week', [AssessmentController::class, 'currentWeek']);
    Route::apiResource('assessments', AssessmentController::class);

    // Quest routes
    Route::get('/quests/current-quarter', [QuestController::class, 'currentQuarter']);
    Route::apiResource('quests', QuestController::class);

    // Belief routes
    Route::get('/beliefs/today', [BeliefController::class, 'today']);
    Route::get('/beliefs/suggestions', [BeliefController::class, 'suggestions']);
    Route::get('/beliefs/weekly-stats', [BeliefController::class, 'weeklyStats']);
    Route::patch('/beliefs/{id}/reflection', [BeliefController::class, 'updateReflection']);
    Route::apiResource('beliefs', BeliefController::class)->except(['update']);

    // Habit routes
    Route::get('/habits/today', [HabitController::class, 'today']);
    Route::get('/habits/summary', [HabitController::class, 'summary']);
    Route::post('/habits/{id}/archive', [HabitController::class, 'archive']);
    Route::get('/habits/{id}/stats', [HabitController::class, 'stats']);
    Route::get('/habits/{id}/entries', [HabitController::class, 'getEntries']);
    Route::post('/habits/{id}/entries', [HabitController::class, 'storeEntry']);
    Route::patch('/habits/{id}/entries/{entryId}', [HabitController::class, 'updateEntry']);
    Route::apiResource('habits', HabitController::class)->except(['update']);
    Route::patch('/habits/{id}', [HabitController::class, 'update']);

    // Experiment routes
    Route::get('/experiments/suggestions', [ExperimentController::class, 'suggestions']);
    Route::get('/experiments/history', [ExperimentController::class, 'history']);
    Route::get('/experiments/check-ins/today', [ExperimentController::class, 'todayCheckIns']);
    Route::post('/experiments/{id}/abandon', [ExperimentController::class, 'abandon']);
    Route::post('/experiments/{id}/complete', [ExperimentController::class, 'complete']);
    Route::get('/experiments/{id}/progress', [ExperimentController::class, 'progress']);
    Route::post('/experiments/{id}/check-ins', [ExperimentController::class, 'addCheckIn']);
    Route::get('/experiments/{id}/check-ins', [ExperimentController::class, 'getCheckIns']);
    Route::apiResource('experiments', ExperimentController::class);
});
