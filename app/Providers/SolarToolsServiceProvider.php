<?php

namespace Pterodactyl\BlueprintFramework\Extensions\solartools\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;

/**
 * ╔═══════════════════════════════════════════════╗
 * ║ SolarToolsServiceProvider                     ║
 * ╚═══════════════════════════════════════════════╝
 */
class SolarToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // ── Blueprint specific views ───────────────────
        $this->loadViewsFrom(__DIR__ . '/../../admin', 'blueprint');

        // ── Intercept Pterodactyl Native Events ────────────
        // The user specifically requested to use native Pterodactyl events.
        // Pterodactyl logs all power actions via its Activity Logger (Spatie / Native).
        // We listen for the ActivityLogged event to capture these natively.
        Event::listen('Pterodactyl\Events\ActivityLogged', function ($event) {
            try {
                if (!isset($event->model) || !isset($event->model->event)) {
                    return;
                }

                $eventName = $event->model->event;

                // Only care about power actions (server:power.start, server:power.stop, etc.)
                if (!str_starts_with($eventName, 'server:power.')) {
                    return;
                }

                $serverModel = null;
                // In Spatie activity log or Pterodactyl's native log, the subject is usually the Server
                if (isset($event->model->subject) && $event->model->subject instanceof Server) {
                    $serverModel = $event->model->subject;
                } elseif (isset($event->model->server_id)) {
                    // Pterodactyl 1.x custom activity logger often has server_id
                    $serverModel = Server::find($event->model->server_id);
                }

                if (!$serverModel || empty($serverModel->discord_webhook)) {
                    return; 
                }

                $signal = str_replace('server:power.', '', $eventName);
                $statusStr = '';
                $color = 0;

                if ($signal === 'start') {
                    $statusStr = '⏳ Iniciando...';
                    $color = hexdec('F1C40F');
                } elseif ($signal === 'stop') {
                    $statusStr = '🛑 Deteniéndose...';
                    $color = hexdec('E67E22');
                } elseif ($signal === 'restart') {
                    $statusStr = '🔄 Reiniciando...';
                    $color = hexdec('3498DB');
                } elseif ($signal === 'kill') {
                    $statusStr = '💀 Detenido Forzosamente';
                    $color = hexdec('E74C3C');
                } else {
                    return;
                }

                $embeds = [[
                    'title' => $statusStr,
                    'description' => "Se ha ejecutado la acción `{$signal}` en el servidor **{$serverModel->name}**.",
                    'color' => $color,
                    'timestamp' => now()->toIso8601String(),
                    'footer' => ['text' => 'SolarCloud Panel']
                ]];

                Http::post($serverModel->discord_webhook, ['embeds' => $embeds]);
                Log::info("[SolarTools] Webhook nativo enviado para servidor {$serverModel->name} (Evento: {$eventName})");
                
            } catch (\Exception $e) {
                Log::error('[SolarTools] Error en el Listener nativo de Webhook: ' . $e->getMessage());
            }
        });

        // ── Fallback: Also listen for the Laravel ActivityLog eloquent event (Spatie)
        Event::listen('eloquent.created: Spatie\Activitylog\Models\Activity', function ($model) {
            try {
                if (isset($model->log_name) && str_starts_with($model->description, 'server:power.')) {
                    // Similar logic here just in case it's older Pterodactyl using raw Spatie
                    $signal = str_replace('server:power.', '', $model->description);
                    if (isset($model->subject_type) && str_contains($model->subject_type, 'Server')) {
                        $serverModel = Server::find($model->subject_id);
                        if ($serverModel && !empty($serverModel->discord_webhook)) {
                            // ... trigger webhook
                        }
                    }
                }
            } catch (\Exception $e) {}
        });
    }
}
