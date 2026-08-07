<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\CaptureAgentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Consumed by the desktop screen-capture agent (a separate app, not this
// codebase) — never by the browser. See app/Http/Controllers/Api/AgentController.php.
Route::prefix('agent')->name('api.agent.')->group(function () {
    Route::post('/login', [AgentController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/config', [AgentController::class, 'config'])->name('config');
        Route::post('/screenshots', [AgentController::class, 'storeScreenshot'])->name('screenshots.store');
    });
});

// Hostname-mapped agent ingestion — no per-employee login, gated instead by
// a single shared secret (AGENT_SHARED_SECRET) checked inside the
// controller. See app/Http/Controllers/Api/CaptureAgentController.php and
// desktop-agent/agent.js.
Route::post('/v1/agent/capture', [CaptureAgentController::class, 'store'])->name('api.v1.agent.capture');
