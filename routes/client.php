<?php

/*
|--------------------------------------------------------------------------
| SolarTools - Client API Routes
|--------------------------------------------------------------------------
| Prefixed by /api/client/extensions/solartools/
| Requires valid client API authentication.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers\SolarAIController;
use Pterodactyl\BlueprintFramework\Extensions\solartools\Controllers\WebhookController;

// ── Solar AI ─────────────────────────────────────────
// POST /api/client/extensions/solartools/ai/analyze
Route::post('/ai/analyze', [SolarAIController::class, 'analyze'])
    ->name('solartools.ai.analyze');

// ── Webhook Management ───────────────────────────────
// GET  /api/client/extensions/solartools/webhook/{server_uuid}
Route::get('/webhook/{server_uuid}', [WebhookController::class, 'show'])
    ->name('solartools.webhook.show');

// POST /api/client/extensions/solartools/webhook/{server_uuid}
Route::post('/webhook/{server_uuid}', [WebhookController::class, 'store'])
    ->name('solartools.webhook.store');

// POST /api/client/extensions/solartools/webhook/{server_uuid}/test
Route::post('/webhook/{server_uuid}/test', [WebhookController::class, 'test'])
    ->name('solartools.webhook.test');
