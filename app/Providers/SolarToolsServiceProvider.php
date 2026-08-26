<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Server;
use Pterodactyl\Events\ActivityLogged;

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║ SolarToolsServiceProvider                         ║
 * ║                                                   ║
 * ║ Listens to Pterodactyl's native ActivityLogged    ║
 * ║ event to capture power actions and send Discord   ║
 * ║ webhook notifications.                            ║
 * ╚═══════════════════════════════════════════════════╝
 *
 * How it works (confirmed by reading Pterodactyl source code):
 *
 * 1. PowerController calls Activity::event('server:power.start')->log()
 * 2. ActivityLogService::save() saves the ActivityLog model inside a DB transaction
 * 3. ActivityLog::boot() fires Event::dispatch(new ActivityLogged($model)) on 'created'
 * 4. The subjects (Server) are inserted into activity_log_subjects in the same transaction
 * 5. We listen for ActivityLogged, then AFTER the transaction commits, we query for
 *    the Server subject and send the Discord webhook.
 */
class SolarToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Blueprint specific views ───────────────────
        $this->loadViewsFrom(__DIR__ . '/../../admin', 'blueprint');

        Log::debug('[SolarTools] ServiceProvider booted successfully');

        // ── Listen for Pterodactyl's native ActivityLogged event ───
        Event::listen(ActivityLogged::class, function (ActivityLogged $event) {
            try {
                $activityLog = $event->model;
                $eventName = $activityLog->event ?? '';

                // Only care about power events
                if (!str_starts_with($eventName, 'server:power.')) {
                    return;
                }

                $signal = str_replace('server:power.', '', $eventName);

                Log::debug('[SolarTools] Power event captured from ActivityLog', [
                    'event'    => $eventName,
                    'signal'   => $signal,
                    'log_id'   => $activityLog->id,
                ]);

                // The ActivityLogged event fires inside a DB transaction.
                // The subjects (Server) are inserted AFTER the ActivityLog model is saved,
                // but still within the same transaction. We use DB::afterCommit() to ensure
                // the subjects are available when we query them.
                DB::afterCommit(function () use ($activityLog, $signal, $eventName) {
                    try {
                        // Reload the activity log with its subjects
                        $activityLog->refresh();
                        $activityLog->load('subjects');

                        // Find the Server subject
                        $serverModel = null;
                        foreach ($activityLog->subjects as $subjectPivot) {
                            if ($subjectPivot->subject_type === (new Server())->getMorphClass()) {
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
                            Log::debug('[SolarTools] No webhook for server', ['name' => $serverModel->name]);
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
                        ]);
                    }
                });

            } catch (\Throwable $e) {
                Log::error('[SolarTools] ❌ Error en ActivityLogged listener', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        });
    }
}
