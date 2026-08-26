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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Server;
use Pterodactyl\Events\ActivityLogged;
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

// POST /api/client/extensions/solartools/webhook/{server_uuid}/notify
Route::post('/webhook/{server_uuid}/notify', [WebhookController::class, 'notify'])
    ->name('solartools.webhook.notify');


/*
|--------------------------------------------------------------------------
| SolarTools - Discord Webhook Event Listener
|--------------------------------------------------------------------------
| This listens to Pterodactyl's native ActivityLogged event which fires
| every time an activity log entry is created (confirmed from source code).
|
| Chain: PowerController → Activity::event('server:power.start')->log()
|      → ActivityLogService::save() → ActivityLog::boot()
|      → Event::dispatch(new ActivityLogged($model))
|
| We place this here instead of a ServiceProvider because Blueprint
| does NOT auto-register extension ServiceProviders. Route files are
| the only PHP files guaranteed to be loaded by Blueprint's
| RouteServiceProvider.
|--------------------------------------------------------------------------
*/

Event::listen(ActivityLogged::class, function (ActivityLogged $event) {
    try {
        $activityLog = $event->model;
        $eventName = $activityLog->event ?? '';

        // Only care about power events: server:power.start, server:power.stop, etc.
        if (!str_starts_with($eventName, 'server:power.')) {
            return;
        }

        $signal = str_replace('server:power.', '', $eventName);

        Log::info('[SolarTools] Power event captured', [
            'event'  => $eventName,
            'signal' => $signal,
            'log_id' => $activityLog->id,
        ]);

        // The ActivityLogged event fires inside a DB transaction (ActivityLogService::save).
        // The subjects (Server) are inserted AFTER the ActivityLog model is saved,
        // but still within the same transaction. We use DB::afterCommit() to ensure
        // the subjects are available when we query them.
        DB::afterCommit(function () use ($activityLog, $signal) {
            try {
                // Reload the activity log with its subjects
                $activityLog->refresh();
                $activityLog->load('subjects');

                // Find the Server subject from the activity_log_subjects pivot table
                $serverModel = null;
                foreach ($activityLog->subjects as $subjectPivot) {
                    if (str_contains($subjectPivot->subject_type, 'Server')) {
                        $serverModel = Server::find($subjectPivot->subject_id);
                        break;
                    }
                }

                if (!$serverModel) {
                    Log::debug('[SolarTools] No Server subject found in activity log', [
                        'log_id'   => $activityLog->id,
                        'subjects' => $activityLog->subjects->toArray(),
                    ]);
                    return;
                }

                $webhookUrl = $serverModel->discord_webhook ?? null;

                if (empty($webhookUrl)) {
                    Log::debug('[SolarTools] No webhook configured for server', [
                        'name' => $serverModel->name,
                        'id'   => $serverModel->id,
                    ]);
                    return;
                }

                // Map signals to human-readable status and colors
                $map = [
                    'start'   => ['status' => '⏳ Iniciando...',            'color' => 0xF1C40F],
                    'stop'    => ['status' => '🛑 Deteniéndose...',          'color' => 0xE67E22],
                    'restart' => ['status' => '🔄 Reiniciando...',           'color' => 0x3498DB],
                    'kill'    => ['status' => '💀 Detenido Forzosamente',    'color' => 0xE74C3C],
                ];

                if (!isset($map[$signal])) {
                    Log::debug('[SolarTools] Unknown power signal', ['signal' => $signal]);
                    return;
                }

                $embeds = [[
                    'title'       => $map[$signal]['status'],
                    'description' => "Se ha ejecutado la acción `{$signal}` en el servidor **{$serverModel->name}**.",
                    'color'       => $map[$signal]['color'],
                    'timestamp'   => now()->toIso8601String(),
                    'footer'      => ['text' => 'SolarCloud Panel'],
                ]];

                $response = Http::timeout(5)->post($webhookUrl, ['embeds' => $embeds]);

                Log::info("[SolarTools] ✅ Webhook enviado para {$serverModel->name} (acción: {$signal})", [
                    'discord_status' => $response->status(),
                ]);

            } catch (\Throwable $e) {
                Log::error('[SolarTools] ❌ Error en afterCommit webhook', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        });

    } catch (\Throwable $e) {
        Log::error('[SolarTools] ❌ Error en ActivityLogged listener', [
            'message' => $e->getMessage(),
        ]);
    }
});
